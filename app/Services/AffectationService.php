<?php

namespace App\Services;

use App\Interfaces\AffectationInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\Affectation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property AffectationInterface $repository */
class AffectationService extends BaseService
{
    public function __construct(
        AffectationInterface $repository,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['statut']     = 'en_attente_validation';

        return $data;
    }

    protected function afterCreate($model): Affectation
    {
        $this->workflowRepository->initialiserCircuit(Affectation::class, $model->id);

        $this->historiqueRepository->enregistrer(
            Affectation::class,
            $model->id,
            Auth::id(),
            'affectation_creee',
            null,
            $model->toArray(),
            null
        );

        return $model;
    }

    public function approuver(int $id): Affectation
    {
        return DB::transaction(function () use ($id) {
            $affectation = $this->repository->findById($id);
            $affectation->update(['statut' => 'approuvee']);

            $this->historiqueRepository->enregistrer(
                Affectation::class,
                $id,
                Auth::id(),
                'affectation_approuvee',
                ['statut' => 'en_attente_validation'],
                ['statut' => 'approuvee'],
                null
            );

            return $affectation->fresh();
        });
    }

    public function activer(int $id): Affectation
    {
        return DB::transaction(function () use ($id) {
            $affectation = $this->repository->findById($id);

            $ancienneActive = $this->repository->getActive($affectation->agent_id);
            if ($ancienneActive && $ancienneActive->id !== $id) {
                $this->repository->terminer($ancienneActive->id, null);
            }

            $affectation->update(['statut' => 'active']);

            return $affectation->fresh();
        });
    }

    public function rejeter(int $id, string $commentaire): Affectation
    {
        $affectation = $this->repository->findById($id);
        $affectation->update(['statut' => 'rejetee']);

        return $affectation->fresh();
    }

    public function terminer(int $id, ?string $dateFin = null): Affectation
    {
        return $this->repository->terminer($id, $dateFin);
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function getActive(int $agentId): ?Affectation
    {
        return $this->repository->getActive($agentId);
    }
}
