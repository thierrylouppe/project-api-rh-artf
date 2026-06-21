<?php

namespace App\Repositories;

use App\Interfaces\PriseDeServiceInterface;
use App\Models\PriseDeService;

class PriseDeServiceRepository extends BaseRepository implements PriseDeServiceInterface
{
    protected function model(): string
    {
        return PriseDeService::class;
    }

    public function findByAgent(int $agentId): ?PriseDeService
    {
        return PriseDeService::where('agent_id', $agentId)->latest()->first();
    }
}
