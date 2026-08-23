<?php

namespace App\Services;

use App\Enums\StatutNomination;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\Nomination;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property NominationInterface $repository */
class NominationService extends BaseService
{
    public function __construct(
        NominationInterface $repository,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['statut']     = StatutNomination::EN_ATTENTE;

        return $data;
    }

    protected function afterCreate($model): Nomination
    {
        $this->workflowRepository->initialiserCircuit(Nomination::class, $model->id);

        $this->historiqueRepository->enregistrer(
            Nomination::class,
            $model->id,
            Auth::id(),
            'nomination_creee',
            null,
            $model->toArray(),
            null
        );

        return $model;
    }

    public function approuver(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::APPROUVEE),
                422,
                "La nomination ne peut pas être approuvée depuis le statut « {$nomination->statut->label()} »."
            );

            $ancienStatut = $nomination->statut;
            $nomination->update(['statut' => StatutNomination::APPROUVEE]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_approuvee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutNomination::APPROUVEE->value],
                null
            );

            return $nomination->fresh();
        });
    }

    public function activer(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::ACTIVE),
                422,
                "La nomination ne peut être activée que depuis le statut « Approuvée ». Statut actuel : « {$nomination->statut->label()} »."
            );

            $this->repository->cloturerActivesPourStructure(
                $nomination->structurable_type,
                $nomination->structurable_id,
                $id
            );
            $this->repository->cloturerActivePourAgent($nomination->agent_id, $id);

            $nomination->update([
                'statut'     => StatutNomination::ACTIVE,
                'date_debut' => $nomination->date_debut ?? now()->toDateString(),
            ]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_activee',
                ['statut' => StatutNomination::APPROUVEE->value],
                ['statut' => StatutNomination::ACTIVE->value, 'poste' => $nomination->poste],
                null
            );

            return $nomination->fresh();
        });
    }

    public function cloturer(int $id, ?string $dateFin = null): Nomination
    {
        return DB::transaction(function () use ($id, $dateFin) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::CLOTUREE),
                422,
                "La nomination ne peut être clôturée que depuis le statut « Active »."
            );

            $cloturee = $this->repository->cloturer($id, $dateFin);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_cloturee',
                ['statut' => StatutNomination::ACTIVE->value],
                ['statut' => StatutNomination::CLOTUREE->value],
                null
            );

            return $cloturee;
        });
    }

    public function rejeter(int $id, string $commentaire): Nomination
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::REJETEE),
                422,
                "La nomination ne peut pas être rejetée depuis le statut « {$nomination->statut->label()} »."
            );

            $ancienStatut = $nomination->statut;
            $nomination->update(['statut' => StatutNomination::REJETEE]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_rejetee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutNomination::REJETEE->value],
                $commentaire
            );

            return $nomination->fresh();
        });
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function getActive(int $agentId): ?Nomination
    {
        return $this->repository->getActive($agentId);
    }
}
