<?php

namespace App\Services;

use App\Interfaces\ContratInterface;
use App\Models\Contrat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ContratService extends BaseService
{
    public function __construct(
        ContratInterface $repository,
        private readonly SalaireAgentService $salaireAgentService,
    ) {
        parent::__construct($repository);
    }

    public function create(array $data): Model
    {
        return DB::transaction(fn () => parent::create($data));
    }

    protected function afterCreate(Model $model): Model
    {
        /** @var Contrat $model */
        $model->loadMissing(['agent', 'typeContrat']);

        $this->salaireAgentService->creerSalaireInitial($model->agent, $model);

        return $model;
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function getActif(int $agentId): ?Contrat
    {
        return $this->repository->getActif($agentId);
    }

    public function resilier(int $id): Contrat
    {
        return $this->repository->resilier($id);
    }
}
