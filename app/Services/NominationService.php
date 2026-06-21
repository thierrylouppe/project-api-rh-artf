<?php

namespace App\Services;

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
        $data['statut']     = 'en_attente';

        return $data;
    }

    protected function afterCreate($model): Nomination
    {
        $this->workflowRepository->initialiserCircuit(Nomination::class, $model->id);

        return $model;
    }

    public function activer(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            // Clôture automatique de la nomination active pour cette structure
            $this->repository->cloturerNominationsActives(
                $nomination->structurable_type,
                $nomination->structurable_id
            );

            $nomination->update([
                'statut'     => 'active',
                'date_debut' => $nomination->date_debut ?? now()->toDateString(),
            ]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_activee',
                null,
                ['statut' => 'active', 'poste' => $nomination->poste],
                null
            );

            return $nomination->fresh();
        });
    }

    public function cloturer(int $id, ?string $dateFin = null): Nomination
    {
        $nomination = $this->repository->findById($id);
        $nomination->update([
            'statut'   => 'cloturee',
            'date_fin' => $dateFin ?? now()->toDateString(),
        ]);

        return $nomination->fresh();
    }

    public function rejeter(int $id, string $commentaire): Nomination
    {
        $nomination = $this->repository->findById($id);
        $nomination->update(['statut' => 'rejetee']);

        return $nomination->fresh();
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
