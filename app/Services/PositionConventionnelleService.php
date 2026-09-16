<?php

namespace App\Services;

use App\Enums\StatutAgent;
use App\Enums\StatutPositionConventionnelle;
use App\Enums\TypePositionConventionnelle;
use App\Interfaces\AgentInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\PositionConventionnelleInterface;
use App\Models\Agent;
use App\Models\PositionConventionnelle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @property PositionConventionnelleInterface $repository */
class PositionConventionnelleService extends BaseService
{
    public function __construct(
        PositionConventionnelleInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly SalaireAgentService $salaireAgentService,
        private readonly NominationInterface $nominationRepository,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct($repository);
    }

    public function parAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->parAgent($agentId);
    }

    public function detail(int $id): PositionConventionnelle
    {
        /** @var PositionConventionnelle $position */
        $position = $this->repository->findById($id);
        $position->load(['agent.affectationActive']);
        $position->setAttribute('prochaine_etape', $this->prochaineEtape($position));

        return $position;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function soumettre(array $data, User $rh): PositionConventionnelle
    {
        $type  = TypePositionConventionnelle::from($data['type']);
        $agent = $this->agentRepository->findById((int) $data['agent_id']);

        $this->assertAgentEligible($agent, $type);
        $this->assertPasDeDossierEnCours((int) $agent->id);
        $this->assertReglesType($type, $data);

        /** @var PositionConventionnelle $position */
        $position = $this->repository->create([
            'agent_id'             => $agent->id,
            'type'                 => $type->value,
            'statut'               => StatutPositionConventionnelle::SOUMISE->value,
            'date_debut'           => $data['date_debut'],
            'date_fin'             => $data['date_fin'],
            'organisme_accueil'    => $data['organisme_accueil'] ?? null,
            'consentement_agent'   => (bool) ($data['consentement_agent'] ?? false),
            'detachement_office'   => (bool) ($data['detachement_office'] ?? false),
            'nb_renouvellements'   => 0,
            'commentaire'          => $data['commentaire'] ?? null,
            'piece_path'           => $data['piece_path'] ?? null,
            'created_by'           => $rh->id,
        ]);

        $this->notifier($position, 'soumise', sprintf(
            'Une demande de %s a été soumise pour %s (art. %s).',
            $type->label(),
            $agent->nom_complet,
            $type->article()
        ));

        return $this->detail($position->id);
    }

    public function approuver(int $id, User $user, ?string $commentaire = null): PositionConventionnelle
    {
        $this->assertDecideurDg($user);

        return DB::transaction(function () use ($id, $user, $commentaire) {
            $position = $this->trouverSoumise($id);
            $type     = $position->type;
            $debut    = $position->date_debut?->toDateString() ?? now()->toDateString();

            $this->repository->update($id, [
                'statut'              => StatutPositionConventionnelle::ACTIVE->value,
                'decision_dg_user_id' => $user->id,
                'commentaire'         => $commentaire ?? $position->commentaire,
            ]);

            $this->agentRepository->update((int) $position->agent_id, [
                'statut' => $type->statutAgent()->value,
            ]);

            if ($type->coupeRemuneration()) {
                $this->salaireAgentService->cloturerActuel(
                    (int) $position->agent_id,
                    $debut,
                    sprintf('Position %s art. %s — rémunération interrompue.', $type->label(), $type->article())
                );
            }

            if ($type->clotureNomination()) {
                $this->nominationRepository->cloturerActivePourAgent((int) $position->agent_id);
            }

            $position = $this->detail($id);
            $this->notifier($position, 'approuvee', sprintf(
                'La position %s de %s a été approuvée (art. %s).',
                $type->label(),
                $position->agent?->nom_complet ?? 'l\'agent',
                $type->article()
            ));

            return $position;
        });
    }

    public function rejeter(int $id, User $user, ?string $commentaire = null): PositionConventionnelle
    {
        $this->assertDecideurDg($user);

        $this->trouverSoumise($id);

        $this->repository->update($id, [
            'statut'              => StatutPositionConventionnelle::REJETEE->value,
            'decision_dg_user_id' => $user->id,
            'commentaire'         => $commentaire,
        ]);

        $position = $this->detail($id);
        $this->notifier($position, 'rejetee', sprintf(
            'La demande de %s de %s a été rejetée.',
            $position->type->label(),
            $position->agent?->nom_complet ?? 'l\'agent'
        ));

        return $position;
    }

    /**
     * @param  array{date_fin?: string|null, commentaire?: string|null}  $data
     */
    public function cloturer(int $id, array $data = []): PositionConventionnelle
    {
        return DB::transaction(function () use ($id, $data) {
            $position = $this->trouverActive($id);
            $dateFin  = $data['date_fin'] ?? now()->toDateString();

            $this->assertPreavisCloture($position, $dateFin);

            $this->repository->update($id, [
                'statut'      => StatutPositionConventionnelle::CLOTUREE->value,
                'date_fin'    => $dateFin,
                'commentaire' => $data['commentaire'] ?? $position->commentaire,
            ]);

            $this->agentRepository->update((int) $position->agent_id, [
                'statut' => StatutAgent::ACTIF->value,
            ]);

            $position = $this->detail($id);

            if (
                $position->type === TypePositionConventionnelle::DETACHEMENT
                && $position->agent?->affectationActive === null
            ) {
                $position->setAttribute('reintegration', [
                    'affectation_manquante' => true,
                    'message'               => 'Réintégrer l\'agent sur un emploi de sa classe (art. 78) : aucune affectation active.',
                ]);
            }

            $this->notifier($position, 'cloturee', sprintf(
                'La position %s de %s est clôturée. L\'agent est réintégré.',
                $position->type->label(),
                $position->agent?->nom_complet ?? 'l\'agent'
            ));

            return $position;
        });
    }

    /**
     * @param  array{date_debut: string, date_fin: string}  $data
     */
    public function renouveler(int $id, array $data, User $user): PositionConventionnelle
    {
        $this->assertDecideurDg($user);

        return DB::transaction(function () use ($id, $data, $user) {
            $position = $this->trouverActive($id);
            $type     = $position->type;

            if (! $type->peutRenouveler((int) $position->nb_renouvellements)) {
                $max = $type->maxRenouvellements();
                throw ValidationException::withMessages([
                    'renouveler' => $max !== null
                        ? sprintf('La mise en disponibilité n\'est renouvelable que %d fois (art. 79).', $max)
                        : 'Cette position n\'est pas renouvelable.',
                ]);
            }

            $payload = array_merge($data, [
                'detachement_office' => (bool) $position->detachement_office,
                'consentement_agent' => true,
                'organisme_accueil'  => $position->organisme_accueil,
            ]);

            $this->assertReglesType($type, $payload);

            $this->repository->update($id, [
                'date_debut'          => $data['date_debut'],
                'date_fin'            => $data['date_fin'],
                'nb_renouvellements'  => (int) $position->nb_renouvellements + 1,
                'decision_dg_user_id' => $user->id,
            ]);

            $position = $this->detail($id);
            $this->notifier($position, 'renouvelee', sprintf(
                'La position %s de %s a été renouvelée jusqu\'au %s.',
                $type->label(),
                $position->agent?->nom_complet ?? 'l\'agent',
                Carbon::parse($data['date_fin'])->format('d/m/Y')
            ));

            return $position;
        });
    }

    public function notifierEcheances(int $jours): int
    {
        $positions = $this->repository->getActivesEcheantLe(now()->addDays($jours)->toDateString());
        if ($positions->isEmpty()) {
            return 0;
        }

        $destinatairesRh = $this->notificationService->destinatairesRoleEtAgent('rh', null);
        $count           = 0;

        foreach ($positions as $position) {
            $message = $jours === 0
                ? sprintf(
                    'La position %s de %s arrive à échéance aujourd\'hui (%s).',
                    $position->type->label(),
                    $position->agent?->nom_complet ?? 'un agent',
                    $position->date_fin?->format('d/m/Y') ?? '—'
                )
                : sprintf(
                    'La position %s de %s arrive à échéance dans %d jour(s) (fin le %s).',
                    $position->type->label(),
                    $position->agent?->nom_complet ?? 'un agent',
                    $jours,
                    $position->date_fin?->format('d/m/Y') ?? '—'
                );

            $destinataires = $destinatairesRh->concat(
                $this->notificationService->destinatairesAuteurEtAgent(null, (int) $position->agent_id)
            );

            $this->notifier($position, 'echeance', $message, ['jours' => $jours], $destinataires);
            $count++;
        }

        return $count;
    }

    private function assertAgentEligible(Agent $agent, TypePositionConventionnelle $type): void
    {
        if ($agent->statut !== StatutAgent::ACTIF->value) {
            throw ValidationException::withMessages([
                'agent_id' => 'Seuls les agents actifs peuvent être placés en position conventionnelle.',
            ]);
        }

        $min = $type->ancienneteMinimaleAnnees();
        if ($min === null) {
            return;
        }

        if ($agent->date_prise_service === null) {
            throw ValidationException::withMessages([
                'anciennete' => 'La date de prise de service est requise pour calculer l\'ancienneté.',
            ]);
        }

        if ($agent->date_prise_service->copy()->startOfDay()->addYears($min)->isAfter(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'anciennete' => sprintf(
                    'Ancienneté ARTF insuffisante : %d ans requis (art. %s).',
                    $min,
                    $type->article()
                ),
            ]);
        }
    }

    private function assertPasDeDossierEnCours(int $agentId): void
    {
        if ($this->repository->existeEnCoursPourAgent($agentId)) {
            throw ValidationException::withMessages([
                'agent_id' => 'Une position conventionnelle est déjà en cours pour cet agent.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertReglesType(TypePositionConventionnelle $type, array $data): void
    {
        $debut = Carbon::parse($data['date_debut'])->startOfDay();
        $fin   = Carbon::parse($data['date_fin'])->startOfDay();

        if ($fin->lt($debut)) {
            throw ValidationException::withMessages([
                'date_fin' => 'La date de fin doit être postérieure ou égale à la date de début.',
            ]);
        }

        $dureeMax = $type->dureeMaximaleAnnees();
        if ($dureeMax !== null && $debut->copy()->addYears($dureeMax)->lt($fin)) {
            throw ValidationException::withMessages([
                'date_fin' => sprintf(
                    'La durée maximale est de %d an(s) par période (art. %s).',
                    $dureeMax,
                    $type->article()
                ),
            ]);
        }

        $office = $type === TypePositionConventionnelle::DETACHEMENT
            && (bool) ($data['detachement_office'] ?? false);

        if ($type === TypePositionConventionnelle::DETACHEMENT && ! $office && ! ($data['consentement_agent'] ?? false)) {
            throw ValidationException::withMessages([
                'consentement_agent' => 'Le consentement de l\'agent est obligatoire pour un détachement (art. 78), sauf détachement d\'office.',
            ]);
        }

        if ($type === TypePositionConventionnelle::DETACHEMENT && blank($data['organisme_accueil'] ?? null)) {
            throw ValidationException::withMessages([
                'organisme_accueil' => 'L\'organisme d\'accueil est obligatoire pour un détachement (art. 78).',
            ]);
        }

        $preavis = $type->preavisMois();
        if ($preavis > 0 && ! $office) {
            $seuil = now()->startOfDay()->addMonths($preavis);
            if ($fin->lt($seuil)) {
                throw ValidationException::withMessages([
                    'date_fin' => sprintf(
                        'Préavis de %d mois : la date de fin doit être au moins au %s (art. %s).',
                        $preavis,
                        $seuil->format('d/m/Y'),
                        $type->article()
                    ),
                ]);
            }
        }
    }

    private function assertPreavisCloture(PositionConventionnelle $position, string $dateFin): void
    {
        $type   = $position->type;
        $preavis = $type->preavisMois();
        $office  = $type === TypePositionConventionnelle::DETACHEMENT && $position->detachement_office;

        if ($preavis === 0 || $office) {
            return;
        }

        $fin   = Carbon::parse($dateFin)->startOfDay();
        $seuil = now()->startOfDay()->addMonths($preavis);

        if ($fin->lt($seuil)) {
            throw ValidationException::withMessages([
                'date_fin' => sprintf(
                    'Préavis de %d mois à la clôture (art. %s) : date de fin au moins au %s.',
                    $preavis,
                    $type->article(),
                    $seuil->format('d/m/Y')
                ),
            ]);
        }
    }

    private function assertDecideurDg(User $user): void
    {
        if ($user->hasAnyRole(['admin', 'directeur-general'])) {
            return;
        }

        abort(403, 'Seuls le directeur général ou un administrateur peuvent prononcer une position conventionnelle.');
    }

    private function trouverSoumise(int $id): PositionConventionnelle
    {
        /** @var PositionConventionnelle $position */
        $position = $this->repository->findById($id);

        if ($position->statut !== StatutPositionConventionnelle::SOUMISE) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une demande soumise peut être traitée.',
            ]);
        }

        return $position;
    }

    private function trouverActive(int $id): PositionConventionnelle
    {
        /** @var PositionConventionnelle $position */
        $position = $this->repository->findById($id);
        $position->loadMissing('agent');

        if ($position->statut !== StatutPositionConventionnelle::ACTIVE) {
            throw ValidationException::withMessages([
                'statut' => 'Seule une position active peut faire l\'objet de cette action.',
            ]);
        }

        return $position;
    }

    private function prochaineEtape(PositionConventionnelle $position): ?string
    {
        return match ($position->statut) {
            StatutPositionConventionnelle::SOUMISE => 'approuver',
            StatutPositionConventionnelle::ACTIVE  => 'cloturer',
            default                                => null,
        };
    }

    private function notifier(
        PositionConventionnelle $position,
        string $action,
        string $message,
        array $meta = [],
        ?Collection $destinataires = null,
    ): void {
        $this->notificationService->notifierEvenementGroupe(
            $destinataires ?? $this->notificationService->destinatairesRoleEtAgent('rh', (int) $position->agent_id),
            'position',
            $action,
            $message,
            array_merge([
                'position_id' => $position->id,
                'agent_id'    => $position->agent_id,
                'type'        => $position->type->value,
            ], $meta)
        );
    }
}
