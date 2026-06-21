<?php

namespace App\Services;

use App\Interfaces\ContratInterface;
use App\Models\Contrat;
use Illuminate\Support\Collection;

class ContratService extends BaseService
{
    public function __construct(ContratInterface $repository)
    {
        parent::__construct($repository);
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
