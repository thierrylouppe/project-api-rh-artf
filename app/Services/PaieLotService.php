<?php

namespace App\Services;

use App\Enums\CodePaieElement;
use App\Enums\CodeTypeSanction;
use App\Enums\ModeCalculPaieElement;
use App\Enums\PeriodicitePaieElement;
use App\Enums\SensPaieElement;
use App\Enums\SourceDetailPaie;
use App\Enums\StatutAgent;
use App\Enums\StatutPaieLot;
use App\Enums\TypeAyantDroit;
use App\Interfaces\AgentInterface;
use App\Interfaces\AyantDroitInterface;
use App\Interfaces\PaieElementAffectationInterface;
use App\Interfaces\PaieElementInterface;
use App\Interfaces\PaieLotInterface;
use App\Interfaces\SalaireAgentInterface;
use App\Interfaces\SanctionInterface;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
use App\Models\PaieLot;
use App\Models\PaieLotLigne;
use App\Models\SalaireAgent;
use App\Models\Sanction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property PaieLotInterface $repository */
class PaieLotService extends BaseService
{
    public function __construct(
        PaieLotInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly SalaireAgentInterface $salaireAgentRepository,
        private readonly PaieElementInterface $elementRepository,
        private readonly PaieElementAffectationInterface $affectationRepository,
        private readonly AyantDroitInterface $ayantDroitRepository,
        private readonly SanctionInterface $sanctionRepository,
        private readonly PaieCalculService $calcul,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct($repository);
    }

    public function getLignes(int $lotId, array $filters = []): Collection
    {
        $this->repository->findById($lotId);

        return $this->repository->getLignes($lotId, $filters);
    }

    public function getLigne(int $lotId, int $ligneId): PaieLotLigne
    {
        $this->repository->findById($lotId);

        return $this->repository->getLigne($lotId, $ligneId);
    }

    public function getBulletinsAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getLignesClotureesParAgent($agentId);
    }

    public function generer(int $id): PaieLot
    {
        $lot = $this->lot($id);
        abort_unless($lot->statut->peutGenerer(), 422, 'Impossible de générer un lot validé ou clôturé.');

        $recalcul = $lot->statut !== StatutPaieLot::BROUILLON;
        $debut = Carbon::create($lot->annee, $lot->mois, 1)->startOfDay();
        $fin = $debut->copy()->endOfMonth()->startOfDay();
        $elements = $this->elementsIndex();
        $ctx = $this->contexte($debut, $fin, $elements);

        DB::transaction(function () use ($lot, $ctx, $elements) {
            $this->repository->supprimerLignes((int) $lot->id);

            $totalGains = 0;
            $totalRetenues = 0;
            $nbLignes = 0;

            foreach ($this->agentsCandidats($ctx, $elements) as $agent) {
                $resultat = $this->genererLigne($lot, $agent, $ctx);
                $totalGains += $resultat['gains'];
                $totalRetenues += $resultat['retenues'];
                $nbLignes++;
            }

            $this->repository->update((int) $lot->id, [
                'statut' => StatutPaieLot::GENERE->value,
                'generated_at' => now(),
                'generated_by' => Auth::id(),
                'total_gains' => $totalGains,
                'total_retenues' => $totalRetenues,
                'total_net' => $totalGains - $totalRetenues,
                'nb_lignes' => $nbLignes,
                'anomalies' => null,
            ]);
        });

        $lot = $this->lot($id);
        $this->notificationService->notifierRole(
            'rh',
            'paie',
            'genere',
            sprintf('Lot %d/%d généré (%d bulletins).', $lot->mois, $lot->annee, $lot->nb_lignes),
            ['lot_id' => $lot->id],
        );

        $lot->setAttribute('_recalcul', $recalcul);

        return $lot;
    }

    public function controler(int $id): PaieLot
    {
        $lot = $this->lot($id);
        abort_unless($lot->statut->peutControler(), 422, 'Le lot doit être généré avant le contrôle.');

        $debut = Carbon::create($lot->annee, $lot->mois, 1)->startOfDay();
        $fin = $debut->copy()->endOfMonth()->startOfDay();
        $elements = $this->elementsIndex();
        $ctx = $this->contexte($debut, $fin, $elements);
        $agents = $this->agentsPourControle()->keyBy('id');
        $lignes = $this->repository->getLignes((int) $lot->id);
        $anomalies = [];

        foreach ($lignes as $ligne) {
            $agent = $agents->get($ligne->agent_id) ?? $ligne->agent;
            $anomalies = array_merge($anomalies, $this->anomaliesLigne($lot, $ligne, $ctx, $agent instanceof Agent ? $agent : null));
        }

        $anomalies = array_merge($anomalies, $this->anomaliesGlobales($lot, $ctx, $agents));

        $compteParLigne = collect($anomalies)->groupBy('agent_id');
        foreach ($lignes as $ligne) {
            $this->repository->updateLigne((int) $ligne->id, [
                'nb_anomalies' => $compteParLigne->get($ligne->agent_id, collect())->count(),
            ]);
        }

        $this->repository->update((int) $lot->id, [
            'statut' => StatutPaieLot::CONTROLE->value,
            'controle_at' => now(),
            'controle_par' => Auth::id(),
            'anomalies' => $anomalies,
        ]);

        return $this->lot($id);
    }

    public function valider(int $id): PaieLot
    {
        $lot = $this->lot($id);
        abort_unless($lot->statut->peutValider(), 422, 'Le lot doit être contrôlé avant validation.');
        abort_if(
            $lot->nbAnomaliesBloquantes() > 0,
            422,
            'Impossible de valider : des anomalies bloquantes restent à lever.'
        );

        $this->repository->update((int) $lot->id, [
            'statut' => StatutPaieLot::VALIDE->value,
            'valide_at' => now(),
            'valide_par' => Auth::id(),
        ]);

        $lot = $this->lot($id);
        $this->notificationService->notifierRole(
            'rh',
            'paie',
            'valide',
            sprintf('Lot %d/%d validé.', $lot->mois, $lot->annee),
            ['lot_id' => $lot->id],
        );

        return $lot;
    }

    public function cloturer(int $id): PaieLot
    {
        $lot = $this->lot($id);
        abort_unless($lot->statut->peutCloturer(), 422, 'Le lot doit être validé avant clôture.');

        $this->repository->update((int) $lot->id, [
            'statut' => StatutPaieLot::CLOTURE->value,
            'cloture_at' => now(),
            'cloture_par' => Auth::id(),
        ]);

        $lot = $this->lot($id);
        $message = sprintf('Lot %d/%d clôturé.', $lot->mois, $lot->annee);
        $meta = ['lot_id' => $lot->id];
        $this->notificationService->notifierRole('rh', 'paie', 'cloture', $message, $meta);
        $this->notificationService->notifierRole('directeur-general', 'paie', 'cloture', $message, $meta);

        return $lot;
    }

    public function delete(int $id): bool
    {
        $lot = $this->lot($id);
        abort_unless($lot->statut->peutSupprimer(), 422, 'Impossible de supprimer un lot validé ou clôturé.');

        return parent::delete($id);
    }

    protected function beforeCreate(array $data): array
    {
        abort_if(
            $this->repository->findByPeriode((int) $data['annee'], (int) $data['mois']) !== null,
            422,
            'Un lot existe déjà pour cette période.'
        );

        $data['statut'] = StatutPaieLot::BROUILLON->value;

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $lot = $this->lot($id);
        abort_unless($lot->statut->peutModifier(), 422, 'Impossible de modifier un lot validé ou clôturé.');

        return [
            'commentaire' => $data['commentaire'] ?? null,
        ];
    }

    private function lot(int $id): PaieLot
    {
        $lot = $this->repository->findById($id);
        abort_unless($lot instanceof PaieLot, 404, 'Lot de paie introuvable.');

        return $lot;
    }

    /**
     * @return Collection<string, PaieElement>
     */
    private function elementsIndex(): Collection
    {
        return $this->elementRepository->getAll()->keyBy('code');
    }

    /**
     * @param  Collection<string, PaieElement>  $elements
     */
    private function contexte(Carbon $debut, Carbon $fin, Collection $elements): PaieLotContexte
    {
        $debutAnnee = Carbon::create($debut->year, 1, 1)->startOfDay();

        return new PaieLotContexte(
            salaires: $this->salaireAgentRepository->getCouvrantPeriode($debut->toDateString(), $fin->toDateString()),
            affectations: $this->affectationRepository->getCouvrantPeriodeTous($debut->toDateString(), $fin->toDateString()),
            ayants: $this->ayantDroitRepository->getActifsGroupesParAgent(),
            sanctions: $this->sanctionRepository->getPrononceesDepuisTous($debutAnnee->toDateString()),
            misesAPied: $this->sanctionRepository->getMisesAPiedCouvrant($debutAnnee->toDateString(), $fin->toDateString()),
            elements: $elements,
            debut: $debut,
            fin: $fin,
        );
    }

    /**
     * @param  Collection<string, PaieElement>  $elements
     * @return Collection<int, Agent>
     */
    private function agentsCandidats(PaieLotContexte $ctx, Collection $elements): Collection
    {
        return $this->agentsPourControle()
            ->filter(fn (Agent $agent) => $this->estCandidat($agent, $ctx, $elements))
            ->values();
    }

    /** @return Collection<int, Agent> */
    private function agentsPourControle(): Collection
    {
        return $this->agentRepository->getByStatuts([
            StatutAgent::ACTIF->value,
            StatutAgent::SUSPENDU->value,
            StatutAgent::POSITION_EXCEPTIONNELLE->value,
            StatutAgent::SOUS_LE_DRAPEAU->value,
            StatutAgent::STAGIAIRE->value,
        ]);
    }

    /**
     * @param  Collection<string, PaieElement>  $elements
     */
    private function estCandidat(Agent $agent, PaieLotContexte $ctx, Collection $elements): bool
    {
        if ((string) $agent->statut === StatutAgent::STAGIAIRE->value) {
            return $this->affectationsDuMois((int) $agent->id, $ctx, $elements)->isNotEmpty();
        }

        if ($agent->estHorsGrille()) {
            return true;
        }

        return $ctx->salaire((int) $agent->id) !== null;
    }

    /**
     * @return array{gains: int, retenues: int}
     */
    private function genererLigne(PaieLot $lot, Agent $agent, PaieLotContexte $ctx): array
    {
        $salaire = $ctx->salaire((int) $agent->id);
        $horsGrille = $agent->estHorsGrille();
        $affectations = $this->affectationsDuMois((int) $agent->id, $ctx, $ctx->elements);
        $montantFonctionnel = $this->montantAffectationCode($affectations, CodePaieElement::SALAIRE_FONCTIONNEL, 0, $agent, $ctx->debut);
        $montantBase = $horsGrille ? 0 : $this->calcul->arrondirFcfa((float) ($salaire?->montant_base ?? 0));
        $baseAnciennete = $horsGrille ? $montantFonctionnel : $montantBase;
        $codesAffectes = $affectations
            ->map(fn (PaieElementAffectation $item) => $item->element?->code)
            ->filter()
            ->values()
            ->all();

        $details = [];
        $details[] = $this->detailBase($montantBase);

        $details = array_merge(
            $details,
            $this->detailsAuto($lot, $agent, $ctx, $baseAnciennete, $codesAffectes),
            $this->detailsAffectations($affectations, $agent, $baseAnciennete, $salaire, $ctx->debut),
        );

        $gains = 0;
        $retenues = 0;
        foreach ($details as $detail) {
            if ($detail['sens'] === SensPaieElement::RETENUE->value) {
                $retenues += (int) $detail['montant'];
            } else {
                $gains += (int) $detail['montant'];
            }
        }

        $ligne = $this->repository->creerLigne([
            'lot_id' => $lot->id,
            'agent_id' => $agent->id,
            'salaire_agent_id' => $salaire?->id,
            'hors_grille' => $horsGrille,
            'montant_base' => $montantBase,
            'total_gains' => $gains,
            'total_retenues' => $retenues,
            'montant_net' => $gains - $retenues,
            'nb_anomalies' => 0,
            'snapshot_agent' => $this->snapshotAgent($agent, $salaire),
        ]);

        $this->repository->creerDetails((int) $ligne->id, $details);

        return ['gains' => $gains, 'retenues' => $retenues];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailBase(int $montant): array
    {
        return [
            'paie_element_id' => null,
            'code' => $this->calcul->codeSalaireBase(),
            'libelle' => 'Salaire de base',
            'nature' => null,
            'sens' => SensPaieElement::GAIN->value,
            'montant' => $montant,
            'source' => SourceDetailPaie::BASE->value,
            'meta' => null,
        ];
    }

    /**
     * @param  list<string>  $codesAffectes
     * @return list<array<string, mixed>>
     */
    private function detailsAuto(PaieLot $lot, Agent $agent, PaieLotContexte $ctx, int $base, array $codesAffectes): array
    {
        if ((string) $agent->statut === StatutAgent::STAGIAIRE->value) {
            return [];
        }

        $details = [];
        $elements = $ctx->elements;
        $finMois = $ctx->fin;
        $mois = (int) $lot->mois;
        $montantAnciennete = 0;

        $ancienneteEl = $elements->get(CodePaieElement::PRIME_ANCIENNETE->value);
        if ($ancienneteEl instanceof PaieElement && $ancienneteEl->actif) {
            $annees = $this->calcul->anneesRevolues($agent->date_prise_service, $finMois);
            $montantAnciennete = $this->calcul->montantAnciennete($base, $annees);
            if ($montantAnciennete > 0) {
                $details[] = $this->detailElement(
                    $ancienneteEl,
                    $montantAnciennete,
                    SourceDetailPaie::CALCUL_AUTO,
                    ['annees_revolues' => $annees, 'taux' => $this->calcul->tauxAnciennete($annees)],
                );
            }
        }

        if ((int) $lot->mois === 12) {
            $finAnneeEl = $elements->get(CodePaieElement::PRIME_FIN_ANNEE->value);
            if ($finAnneeEl instanceof PaieElement && $finAnneeEl->actif && ! $this->licenciementFauteLourde((int) $agent->id, (int) $lot->annee, $ctx)) {
                $annees = $this->calcul->anneesRevolues($agent->date_prise_service, $finMois);
                if ($annees >= 1 && $base > 0) {
                    $details[] = $this->detailElement(
                        $finAnneeEl,
                        $this->calcul->montantFinAnnee($base, $montantAnciennete),
                        SourceDetailPaie::CALCUL_AUTO,
                    );
                }
            }

            $arbreEl = $elements->get(CodePaieElement::ALLOCATION_ARBRE_NOEL->value);
            if ($arbreEl instanceof PaieElement && $arbreEl->actif && $arbreEl->montant_defaut !== null) {
                $nb = $this->nbEnfantsArbreNoel((int) $agent->id, $finMois, $ctx);
                $details[] = $this->detailElement(
                    $arbreEl,
                    $this->calcul->montantArbreNoel((float) $arbreEl->montant_defaut, $nb),
                    SourceDetailPaie::CALCUL_AUTO,
                    ['nb_enfants_eligibles' => $nb],
                );
            }
        }

        if ((int) $lot->mois === 9) {
            $rentreeEl = $elements->get(CodePaieElement::ALLOCATION_RENTREE_SCOLAIRE->value);
            if ($rentreeEl instanceof PaieElement && $rentreeEl->actif && $rentreeEl->montant_defaut !== null) {
                $details[] = $this->detailElement(
                    $rentreeEl,
                    $this->calcul->montantRentreeScolaire((float) $rentreeEl->montant_defaut),
                    SourceDetailPaie::CALCUL_AUTO,
                );
            }
        }

        $nbEnfants = $this->nbEnfantsACharge((int) $agent->id, $finMois, $ctx);

        $this->ajouterAutoParametre(
            $details,
            $elements,
            $codesAffectes,
            CodePaieElement::PRIME_VESTIMENTAIRE,
            $mois,
            function (PaieElement $element): ?array {
                if ($element->montant_defaut === null) {
                    return null;
                }

                return [$this->calcul->arrondirFcfa((float) $element->montant_defaut), null];
            },
        );

        $this->ajouterAutoParametre(
            $details,
            $elements,
            $codesAffectes,
            CodePaieElement::INDEMNITE_TRANSPORT,
            $mois,
            function (PaieElement $element): ?array {
                if ($element->montant_defaut === null) {
                    return null;
                }

                return [$this->calcul->arrondirFcfa((float) $element->montant_defaut), null];
            },
        );

        $this->ajouterAutoParametre(
            $details,
            $elements,
            $codesAffectes,
            CodePaieElement::ALLOCATIONS_FAMILIALES,
            $mois,
            function (PaieElement $element) use ($nbEnfants): ?array {
                $montant = $this->calcul->montantAllocationsFamiliales((float) ($element->montant_defaut ?? 0), $nbEnfants);
                if ($montant <= 0) {
                    return null;
                }

                return [$montant, ['nb_enfants_a_charge' => $nbEnfants]];
            },
        );

        $this->ajouterAutoParametre(
            $details,
            $elements,
            $codesAffectes,
            CodePaieElement::SUPPLEMENT_FAMILIAL,
            $mois,
            function (PaieElement $element) use ($nbEnfants): ?array {
                if ($element->montant_defaut === null || $nbEnfants <= 0) {
                    return null;
                }

                return [
                    $this->calcul->arrondirFcfa((float) $element->montant_defaut),
                    ['nb_enfants_a_charge' => $nbEnfants],
                ];
            },
        );

        $this->ajouterAutoParametre(
            $details,
            $elements,
            $codesAffectes,
            CodePaieElement::RETENUE_CNSS,
            $mois,
            function (PaieElement $element) use ($base): ?array {
                $taux = (float) ($element->taux_defaut ?? 0);
                $montant = $this->calcul->montantPourcentageBase($base, $taux);
                if ($montant <= 0) {
                    return null;
                }

                return [$montant, ['taux' => $taux, 'base' => $base]];
            },
        );

        return $details;
    }

    /**
     * @param  list<array<string, mixed>>  $details
     * @param  Collection<string, PaieElement>  $elements
     * @param  list<string>  $codesAffectes
     * @param  callable(PaieElement): ?array{0: int, 1: ?array<string, mixed>}  $resolver
     */
    private function ajouterAutoParametre(
        array &$details,
        Collection $elements,
        array $codesAffectes,
        CodePaieElement $code,
        int $mois,
        callable $resolver,
    ): void {
        if (in_array($code->value, $codesAffectes, true)) {
            return;
        }

        $element = $elements->get($code->value);
        if (! $element instanceof PaieElement || ! $element->actif) {
            return;
        }

        if (! $this->periodiciteMatche($element, $mois)) {
            return;
        }

        $resolu = $resolver($element);
        if ($resolu === null) {
            return;
        }

        [$montant, $meta] = $resolu;
        $details[] = $this->detailElement($element, $montant, SourceDetailPaie::CALCUL_AUTO, $meta);
    }

    /**
     * @param  Collection<int, PaieElementAffectation>  $affectations
     * @return list<array<string, mixed>>
     */
    private function detailsAffectations(Collection $affectations, Agent $agent, int $base, ?SalaireAgent $salaire, Carbon $debutMois): array
    {
        $details = [];

        foreach ($affectations as $affectation) {
            $element = $affectation->element;
            if (! $element instanceof PaieElement || ! $element->actif) {
                continue;
            }

            $resolu = $this->resoudreMontantAffectation($affectation, $element, $base, $agent, $salaire);
            if ($resolu === null) {
                continue;
            }

            $meta = $affectation->meta ?? [];
            $meta['affectation_id'] = $affectation->id;
            if ($this->interimDepasse($affectation, $element, $debutMois)) {
                $meta['interim_6_mois'] = true;
            }

            $details[] = $this->detailElement($element, $resolu, SourceDetailPaie::AFFECTATION, $meta);
        }

        return $details;
    }

    /**
     * @return array<string, mixed>
     */
    private function detailElement(PaieElement $element, int $montant, SourceDetailPaie $source, ?array $meta = null): array
    {
        return [
            'paie_element_id' => $element->id,
            'code' => $element->code,
            'libelle' => $element->libelle,
            'nature' => $element->nature?->value,
            'sens' => $element->sens?->value ?? SensPaieElement::GAIN->value,
            'montant' => $montant,
            'source' => $source->value,
            'meta' => $meta ?: null,
        ];
    }

    /**
     * @param  Collection<string, PaieElement>  $elements
     * @return Collection<int, PaieElementAffectation>
     */
    private function affectationsDuMois(int $agentId, PaieLotContexte $ctx, Collection $elements): Collection
    {
        return $ctx->affectationsAgent($agentId)
            ->filter(function (PaieElementAffectation $affectation) use ($elements, $ctx) {
                $element = $affectation->element ?? $elements->get((string) $affectation->element?->code);
                if (! $element instanceof PaieElement || ! $element->actif) {
                    return false;
                }

                return $this->periodiciteMatche($element, (int) $ctx->debut->month);
            })
            ->values();
    }

    private function periodiciteMatche(PaieElement $element, int $mois): bool
    {
        $declenchement = array_map('intval', $element->mois_declenchement ?? []);
        if ($declenchement !== []) {
            return in_array($mois, $declenchement, true);
        }

        return in_array($element->periodicite, [
            PeriodicitePaieElement::MENSUEL,
            PeriodicitePaieElement::PONCTUEL,
            PeriodicitePaieElement::JOURNALIER,
            PeriodicitePaieElement::SEMESTRIEL,
            PeriodicitePaieElement::ANNUEL,
        ], true);
    }

    /**
     * @param  Collection<int, PaieElementAffectation>  $affectations
     */
    private function montantAffectationCode(Collection $affectations, CodePaieElement $code, int $base, Agent $agent, Carbon $debut): int
    {
        $affectation = $affectations->first(
            fn (PaieElementAffectation $item) => $item->element?->code === $code->value
        );

        if (! $affectation instanceof PaieElementAffectation || ! $affectation->element instanceof PaieElement) {
            return 0;
        }

        return $this->resoudreMontantAffectation($affectation, $affectation->element, $base, $agent, null) ?? 0;
    }

    private function resoudreMontantAffectation(
        PaieElementAffectation $affectation,
        PaieElement $element,
        int $base,
        Agent $agent,
        ?SalaireAgent $salaire,
    ): ?int {
        $code = CodePaieElement::tryFrom((string) $element->code);
        $montant = $affectation->montant !== null ? (float) $affectation->montant : (float) ($element->montant_defaut ?? 0);
        $taux = $affectation->taux !== null ? (float) $affectation->taux : (float) ($element->taux_defaut ?? 0);
        $quantite = (float) ($affectation->quantite ?? 0);

        if ($code === CodePaieElement::INDEMNITE_FORMATION) {
            $zone = $affectation->meta['zone'] ?? null;
            if ($zone === 'afrique') {
                return $this->calcul->montantFormationAfrique($base);
            }
            if ($affectation->montant === null) {
                return null;
            }

            return $this->calcul->arrondirFcfa((float) $affectation->montant);
        }

        if ($code === CodePaieElement::INDEMNITE_MISSION_LOCALE) {
            if ($quantite <= 0) {
                return null;
            }
            $localiteSecondaire = (bool) ($affectation->meta['localite_secondaire'] ?? false);
            $indice = $salaire?->salaire?->indice !== null ? (int) $salaire->salaire->indice : null;
            $tauxJour = $this->calcul->tauxJournalierMissionLocale($this->sigleAgent($agent), $indice, $localiteSecondaire);

            return $this->calcul->arrondirFcfa($tauxJour * $quantite);
        }

        if ($code === CodePaieElement::INDEMNITE_MISSION_ETRANGER) {
            if ($quantite <= 0) {
                return null;
            }
            $afrique = ($affectation->meta['zone'] ?? 'afrique') !== 'autre';
            $tauxJour = $this->calcul->tauxJournalierMissionEtranger($this->sigleAgent($agent), $afrique);

            return $this->calcul->arrondirFcfa($tauxJour * $quantite);
        }

        return match ($element->mode_calcul) {
            ModeCalculPaieElement::MONTANT_FIXE => $this->montantFixe($element, $affectation, $montant, $quantite),
            ModeCalculPaieElement::POURCENTAGE_BASE => $taux > 0 && $base > 0
                ? $this->calcul->arrondirFcfa($base * $taux / 100)
                : null,
            default => $montant > 0 ? $this->calcul->arrondirFcfa($montant) : null,
        };
    }

    private function montantFixe(PaieElement $element, PaieElementAffectation $affectation, float $montant, float $quantite): ?int
    {
        if ($affectation->montant === null && $element->montant_defaut === null) {
            return null;
        }

        $journalier = $element->periodicite === PeriodicitePaieElement::JOURNALIER;
        if ($journalier && $quantite <= 0) {
            return null;
        }

        $valeur = $journalier ? $montant * $quantite : $montant;

        return $this->calcul->arrondirFcfa($valeur);
    }

    private function sigleAgent(Agent $agent): ?string
    {
        $agent->loadMissing(['nominationActive', 'fonction']);
        $poste = $agent->nominationActive?->poste;
        if (is_string($poste) && $poste !== '') {
            $map = [
                'Directeur Général' => 'DG',
                'Directeur Central' => 'DC',
                'Directeur Départemental' => 'DD',
            ];
            if (isset($map[$poste])) {
                return $map[$poste];
            }
        }

        return $agent->fonction?->sigle;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotAgent(Agent $agent, ?SalaireAgent $salaire): array
    {
        $agent->loadMissing(['fonction', 'grade']);

        return [
            'id' => $agent->id,
            'matricule' => $agent->matricule,
            'nom' => $agent->nom,
            'prenom' => $agent->prenom,
            'statut' => $agent->statut,
            'fonction' => $agent->fonction?->nom,
            'fonction_sigle' => $agent->fonction?->sigle,
            'grade' => $agent->grade?->nom,
            'classe_id' => $salaire?->classegrillesalariale_id,
            'classe' => $salaire?->classe?->categorie?->sigle,
            'echelon' => $salaire?->echelon,
            'date_prise_service' => $agent->date_prise_service?->toDateString(),
        ];
    }

    private function nbEnfantsArbreNoel(int $agentId, Carbon $au, PaieLotContexte $ctx): int
    {
        return $ctx->ayantsAgent($agentId)
            ->filter(fn ($ayant) => $ayant instanceof AyantDroit && $ayant->estEligibleArbreNoel($au))
            ->count();
    }

    private function nbEnfantsACharge(int $agentId, Carbon $au, PaieLotContexte $ctx): int
    {
        return $ctx->ayantsAgent($agentId)
            ->filter(fn ($ayant) => $ayant instanceof AyantDroit
                && $ayant->type === TypeAyantDroit::ENFANT
                && $ayant->estACharge($au))
            ->count();
    }

    private function licenciementFauteLourde(int $agentId, int $annee, PaieLotContexte $ctx): bool
    {
        return $ctx->sanctionsAgent($agentId)
            ->contains(function (Sanction $sanction) use ($annee) {
                $code = $sanction->typeSanction?->code;
                if ($code !== CodeTypeSanction::LICENCIEMENT) {
                    return false;
                }
                if ($sanction->avec_indemnite !== false) {
                    return false;
                }
                $date = $sanction->date_decision ?? $sanction->created_at;

                return $date !== null && (int) $date->format('Y') === $annee;
            });
    }

    private function interimDepasse(PaieElementAffectation $affectation, PaieElement $element, Carbon $debutMois): bool
    {
        if ($element->code !== CodePaieElement::INDEMNITE_INTERIM->value) {
            return false;
        }

        $cause = $affectation->meta['cause'] ?? null;
        if (in_array($cause, ['maladie', 'accident_travail'], true)) {
            return false;
        }

        return $affectation->date_debut !== null
            && $affectation->date_debut->copy()->addMonths(6)->lte($debutMois);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function anomaliesLigne(PaieLot $lot, PaieLotLigne $ligne, PaieLotContexte $ctx, ?Agent $agent): array
    {
        $anomalies = [];
        $agentId = (int) $ligne->agent_id;
        $codes = $ligne->details->pluck('code');

        if ($ligne->hors_grille && ! $codes->contains(CodePaieElement::SALAIRE_FONCTIONNEL->value)) {
            $anomalies[] = $this->anomalie('hors_grille_sans_fonctionnel', 'bloquante', $agentId, 'Agent hors grille sans salaire fonctionnel sur le mois.');
        }

        if (! $ligne->hors_grille && (float) $ligne->montant_base <= 0) {
            $statut = $ligne->snapshot_agent['statut'] ?? $ligne->agent?->statut;
            if ($statut !== StatutAgent::STAGIAIRE->value) {
                $anomalies[] = $this->anomalie('sans_base', 'bloquante', $agentId, 'Agent actif sans salaire de base sur le mois.');
            }
        }

        if ($agent instanceof Agent) {
            foreach ($this->affectationsDuMois($agentId, $ctx, $ctx->elements) as $affectation) {
                $element = $affectation->element;
                if (! $element instanceof PaieElement) {
                    continue;
                }
                if ($this->resoudreMontantAffectation($affectation, $element, (int) $ligne->montant_base, $agent, $ligne->salaireAgent) === null) {
                    $anomalies[] = $this->anomalie(
                        'montant_element_manquant',
                        'bloquante',
                        $agentId,
                        sprintf('Montant manquant pour %s.', $element->libelle),
                    );
                }
                if ($this->interimDepasse($affectation, $element, $ctx->debut)) {
                    $anomalies[] = $this->anomalie('interim_6_mois', 'bloquante', $agentId, 'Intérim au-delà de six mois sans cause maladie / accident du travail (art. 57).');
                }
            }
        }

        if ($this->sanctionsMiseAPied($agentId, $ctx->debut, $ctx->fin, $ctx)->isNotEmpty()) {
            $anomalies[] = $this->anomalie('mise_a_pied', 'info', $agentId, 'Mise à pied dont les jours tombent dans le mois (prorata non appliqué en V1).');
        }

        if ((int) $lot->mois === 12 && $this->aMiseAPiedDansAnnee($agentId, $ctx)) {
            $anomalies[] = $this->anomalie('fin_annee_mise_a_pied', 'info', $agentId, 'Mise à pied dans l\'année : refus possible de la prime de fin d\'année (art. 56).');
        }

        return $anomalies;
    }

    /**
     * @param  Collection<int, Agent>  $agents
     * @return list<array<string, mixed>>
     */
    private function anomaliesGlobales(PaieLot $lot, PaieLotContexte $ctx, Collection $agents): array
    {
        $anomalies = [];
        $elements = $ctx->elements;

        foreach ($agents as $agent) {
            if (! $agent instanceof Agent || $agent->estHorsGrille()) {
                continue;
            }
            if ((string) $agent->statut === StatutAgent::STAGIAIRE->value) {
                continue;
            }
            if ($ctx->salaire((int) $agent->id) !== null) {
                continue;
            }
            $anomalies[] = $this->anomalie('sans_base', 'bloquante', (int) $agent->id, 'Agent actif sans salaire_agent couvrant le mois.');
        }

        if ((int) $lot->mois === 12) {
            $arbre = $elements->get(CodePaieElement::ALLOCATION_ARBRE_NOEL->value);
            if ($arbre instanceof PaieElement && $arbre->actif && $arbre->montant_defaut === null) {
                $anomalies[] = $this->anomalie('prime_sans_parametre', 'info', null, 'Allocation arbre de Noël ignorée : montant_defaut non paramétré.');
            }

            foreach ($this->agentRepository->getByStatuts([
                StatutAgent::RETRAITE->value,
                StatutAgent::ARCHIVE->value,
                StatutAgent::DETACHEMENT->value,
                StatutAgent::DISPONIBILITE->value,
            ]) as $parti) {
                $anomalies[] = $this->anomalie(
                    'fin_annee_depart',
                    'info',
                    (int) $parti->id,
                    'Départ ou position coupant dans l\'année : prime de fin d\'année à traiter manuellement (prorata / art. 56).',
                );
            }
        }

        if ((int) $lot->mois === 9) {
            $rentree = $elements->get(CodePaieElement::ALLOCATION_RENTREE_SCOLAIRE->value);
            if ($rentree instanceof PaieElement && $rentree->actif && $rentree->montant_defaut === null) {
                $anomalies[] = $this->anomalie('prime_sans_parametre', 'info', null, 'Allocation rentrée scolaire ignorée : montant_defaut non paramétré.');
            }
        }

        return $anomalies;
    }

    /**
     * @return Collection<int, Sanction>
     */
    private function sanctionsMiseAPied(int $agentId, Carbon $debut, Carbon $fin, PaieLotContexte $ctx): Collection
    {
        return $ctx->misesAPiedAgent($agentId)
            ->filter(function (Sanction $sanction) use ($debut, $fin) {
                $d = $sanction->date_debut_effet;
                $f = $sanction->date_fin_effet ?? $d;
                if ($d === null) {
                    return false;
                }

                return $d->lte($fin) && ($f === null || $f->gte($debut));
            })
            ->values();
    }

    private function aMiseAPiedDansAnnee(int $agentId, PaieLotContexte $ctx): bool
    {
        return $ctx->misesAPiedAgent($agentId)->isNotEmpty();
    }

    /**
     * @return array{code: string, severite: string, agent_id: ?int, message: string}
     */
    private function anomalie(string $code, string $severite, ?int $agentId, string $message): array
    {
        return [
            'code' => $code,
            'severite' => $severite,
            'agent_id' => $agentId,
            'message' => $message,
        ];
    }
}
