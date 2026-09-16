<?php

namespace App\Services;

use App\Enums\CodePaieElement;
use App\Enums\ModeCalculPaieElement;
use App\Enums\NaturePaieElement;
use App\Enums\PeriodicitePaieElement;
use App\Enums\StatutAgent;
use App\Interfaces\AgentInterface;
use App\Interfaces\PaieElementAffectationInterface;
use App\Interfaces\PaieElementInterface;
use App\Interfaces\PaieLotInterface;
use App\Models\Agent;
use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property PaieElementAffectationInterface $repository */
class PaieAffectationService extends BaseService
{
    public function __construct(
        PaieElementAffectationInterface $repository,
        private readonly PaieElementInterface $elementRepository,
        private readonly AgentInterface $agentRepository,
        private readonly PaieLotInterface $lotRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    protected function beforeCreate(array $data): array
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $element = $this->elementRepository->findById((int) $data['paie_element_id']);

        abort_unless($agent instanceof Agent, 404, 'Agent introuvable.');
        abort_unless($element instanceof PaieElement, 404, 'Élément de paie introuvable.');

        $data = $this->normaliserMeta($data);
        $this->assertRegles($agent, $element, $data);

        $data['created_by'] = Auth::id();
        $data['prolongation_dg'] = (bool) ($data['prolongation_dg'] ?? false);

        return $data;
    }

    protected function afterCreate($model): PaieElementAffectation
    {
        return $model->load([
            'agent:id,matricule,nom,prenom,statut,fonction_id',
            'agent.fonction:id,nom,sigle',
            'element',
        ]);
    }

    public function delete(int $id): bool
    {
        $affectation = $this->repository->findById($id);
        abort_unless($affectation instanceof PaieElementAffectation, 404, 'Affectation introuvable.');
        $this->assertPasVerrouillee($affectation);

        return parent::delete($id);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $affectation = $this->repository->findById($id);
        abort_unless($affectation instanceof PaieElementAffectation, 404, 'Affectation introuvable.');
        $this->assertPasVerrouillee($affectation);

        unset($data['agent_id'], $data['paie_element_id'], $data['created_by']);

        $agent = $this->agentRepository->findById((int) $affectation->agent_id);
        $element = $this->elementRepository->findById((int) $affectation->paie_element_id);

        abort_unless($agent instanceof Agent, 404, 'Agent introuvable.');
        abort_unless($element instanceof PaieElement, 404, 'Élément de paie introuvable.');

        $merged = array_merge([
            'montant' => $affectation->montant,
            'taux' => $affectation->taux,
            'quantite' => $affectation->quantite,
            'date_debut' => $affectation->date_debut?->toDateString(),
            'date_fin' => $affectation->date_fin?->toDateString(),
            'motif' => $affectation->motif,
            'prolongation_dg' => $affectation->prolongation_dg,
            'meta' => $affectation->meta ?? [],
        ], $data);

        $merged = $this->normaliserMeta($merged);
        $this->assertRegles($agent, $element, $merged, $id);

        if (array_key_exists('meta', $merged)) {
            $data['meta'] = $merged['meta'];
        }
        unset($data['zone'], $data['cause']);
        if (array_key_exists('prolongation_dg', $data)) {
            $data['prolongation_dg'] = (bool) $data['prolongation_dg'];
        }

        return $data;
    }

    protected function afterUpdate($model): PaieElementAffectation
    {
        return $this->afterCreate($model);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertRegles(Agent $agent, PaieElement $element, array $data, ?int $excludeId = null): void
    {
        abort_unless($element->actif, 422, 'Impossible d\'affecter un élément de paie inactif.');

        $code = CodePaieElement::tryFrom((string) $element->code);

        abort_if(
            $code?->estCalculeAuto() === true,
            422,
            'Cet élément est calculé automatiquement à la génération du lot ; il ne s\'affecte pas manuellement.'
        );

        $this->assertStatutAgent($agent, $code, $element);
        $this->assertFonction($agent, $element);
        $this->assertMontant($element, $data, $code);
        $this->assertQuantiteEtMission($element, $data, $code);
        $this->assertMotifExceptionnelle($code, $data);
        $this->assertFormation($code, $data);
        $this->assertInterim($code, $data);
        $this->assertChevauchement($agent, $element, $data, $excludeId);
    }

    private function assertStatutAgent(Agent $agent, ?CodePaieElement $code, PaieElement $element): void
    {
        $statut = (string) $agent->statut;

        abort_if(
            in_array($statut, [
                StatutAgent::ARCHIVE->value,
                StatutAgent::INACTIF->value,
                StatutAgent::RETRAITE->value,
                StatutAgent::DETACHEMENT->value,
                StatutAgent::DISPONIBILITE->value,
            ], true),
            422,
            'Cet agent n\'est pas en position de recevoir une affectation de paie.'
        );

        if ($code === CodePaieElement::PRIME_TRANSPORT_STAGIAIRE) {
            abort_if(
                $statut !== StatutAgent::STAGIAIRE->value,
                422,
                'La prime de transport stagiaire est réservée aux stagiaires (art. 54).'
            );

            return;
        }

        if ($statut === StatutAgent::STAGIAIRE->value) {
            abort_if(
                $element->nature !== NaturePaieElement::RETENUE && $element->systeme,
                422,
                'Un stagiaire ne peut recevoir que la prime de transport (art. 54) ou une retenue.'
            );
        }
    }

    private function assertFonction(Agent $agent, PaieElement $element): void
    {
        $sigles = $element->fonction_sigles;
        if (! is_array($sigles) || $sigles === []) {
            return;
        }

        $sigle = $this->sigleAgent($agent);

        abort_if(
            $sigle === null || ! in_array($sigle, $sigles, true),
            422,
            sprintf('Cet élément est réservé aux fonctions : %s.', implode(', ', $sigles))
        );
    }

    private function sigleAgent(Agent $agent): ?string
    {
        $agent->loadMissing(['fonction', 'nominationActive']);

        $poste = $agent->nominationActive?->poste;
        if (is_string($poste) && $poste !== '') {
            $depuisPoste = match ($poste) {
                'Directeur Général' => 'DG',
                'Directeur Central' => 'DC',
                'Directeur Départemental' => 'DD',
                'Chef de service rattaché' => 'CSR',
                'Chef de service' => 'CS',
                'Chef de bureau' => 'CB',
                'Agent' => 'AGT',
                'Stagiaire' => 'STG',
                default => null,
            };
            if ($depuisPoste !== null) {
                return $depuisPoste;
            }
        }

        return $agent->fonction?->sigle;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMontant(PaieElement $element, array $data, ?CodePaieElement $code): void
    {
        $montant = $data['montant'] ?? null;
        $taux = $data['taux'] ?? null;

        if ($element->mode_calcul === ModeCalculPaieElement::MONTANT_FIXE) {
            abort_if(
                $montant === null && $element->montant_defaut === null,
                422,
                'Indiquez un montant (l\'élément n\'a pas de montant par défaut).'
            );
        }

        if ($element->mode_calcul === ModeCalculPaieElement::POURCENTAGE_BASE) {
            abort_if(
                $taux === null && $element->taux_defaut === null,
                422,
                'Indiquez un taux (l\'élément n\'a pas de taux par défaut).'
            );
        }

        if ($code === CodePaieElement::INDEMNITE_FORMATION) {
            $zone = $data['meta']['zone'] ?? null;
            abort_if(
                $zone === 'autre' && $montant === null,
                422,
                'Indiquez le montant (SMIG du pays d\'accueil) pour une formation hors Afrique.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertQuantiteEtMission(PaieElement $element, array $data, ?CodePaieElement $code): void
    {
        $besoinQuantite = $element->periodicite === PeriodicitePaieElement::JOURNALIER
            || $element->mode_calcul === ModeCalculPaieElement::BAREME_CCN;

        if ($besoinQuantite) {
            abort_if(
                ! isset($data['quantite']) || (float) $data['quantite'] <= 0,
                422,
                'Indiquez une quantité (nombre de jours).'
            );
        }

        if ($code === CodePaieElement::INDEMNITE_MISSION_LOCALE) {
            $jours = (float) ($data['quantite'] ?? 0);
            $prolongation = (bool) ($data['prolongation_dg'] ?? false);
            abort_if(
                $jours > 15 && ! $prolongation,
                422,
                'Une mission locale ne peut excéder 15 jours sans prolongation du directeur général (art. 57).'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertMotifExceptionnelle(?CodePaieElement $code, array $data): void
    {
        if ($code !== CodePaieElement::PRIME_EXCEPTIONNELLE) {
            return;
        }

        abort_if(
            blank($data['motif'] ?? null),
            422,
            'Le motif (note de service du directeur général) est obligatoire pour une prime exceptionnelle.'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertFormation(?CodePaieElement $code, array $data): void
    {
        if ($code !== CodePaieElement::INDEMNITE_FORMATION) {
            return;
        }

        $zone = $data['meta']['zone'] ?? null;
        abort_if(
            ! in_array($zone, ['afrique', 'autre'], true),
            422,
            'Indiquez la zone de formation (afrique ou autre).'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertInterim(?CodePaieElement $code, array $data): void
    {
        if ($code !== CodePaieElement::INDEMNITE_INTERIM) {
            return;
        }

        if (empty($data['date_fin'])) {
            return;
        }

        $debut = Carbon::parse((string) $data['date_debut']);
        $fin = Carbon::parse((string) $data['date_fin']);
        $cause = $data['meta']['cause'] ?? null;

        abort_if(
            $debut->copy()->addMonths(6)->lt($fin)
                && ! in_array($cause, ['maladie', 'accident_travail'], true),
            422,
            'L\'intérim ne peut dépasser six mois, sauf maladie ou accident de travail (art. 57).'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertPasVerrouillee(PaieElementAffectation $affectation): void
    {
        abort_if(
            $this->lotRepository->existeSnapshotVerrouille(
                (int) $affectation->agent_id,
                (int) $affectation->paie_element_id,
            ),
            422,
            'Cette affectation est figée dans un lot de paie validé ou clôturé.'
        );
    }

    private function assertChevauchement(Agent $agent, PaieElement $element, array $data, ?int $excludeId): void
    {
        $chevauche = $this->repository->findChevauchement(
            (int) $agent->id,
            (int) $element->id,
            (string) $data['date_debut'],
            isset($data['date_fin']) ? (string) $data['date_fin'] : null,
            $excludeId,
        );

        abort_if(
            $chevauche !== null,
            422,
            'Une affectation de cet élément existe déjà sur une période qui se chevauche.'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normaliserMeta(array $data): array
    {
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];

        if (isset($data['zone'])) {
            $meta['zone'] = $data['zone'];
        }
        if (isset($data['cause'])) {
            $meta['cause'] = $data['cause'];
        }

        unset($data['zone'], $data['cause']);
        $data['meta'] = $meta === [] ? null : $meta;

        return $data;
    }
}
