<?php

namespace App\Services;

use App\Interfaces\PriseDeServiceInterface;
use App\Models\PriseDeService;

class PriseDeServiceService extends BaseService
{
    public function __construct(PriseDeServiceInterface $repository)
    {
        parent::__construct($repository);
    }

    public function findByAgent(int $agentId): ?PriseDeService
    {
        return $this->repository->findByAgent($agentId);
    }
}
