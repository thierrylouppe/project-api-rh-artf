<?php

namespace App\Repositories;

use App\Interfaces\InformationsPersonnelleInterface;
use App\Models\InformationsPersonnelle;

class InformationsPersonnelleRepository extends BaseRepository implements InformationsPersonnelleInterface
{
    protected function model(): string
    {
        return InformationsPersonnelle::class;
    }

    public function findByAgent(int $agentId): ?InformationsPersonnelle
    {
        return InformationsPersonnelle::query()->where('agent_id', $agentId)->first();
    }

    public function upsertForAgent(int $agentId, array $data): InformationsPersonnelle
    {
        $data['agent_id'] = $agentId;

        return InformationsPersonnelle::query()->updateOrCreate(
            ['agent_id' => $agentId],
            $data
        );
    }
}
