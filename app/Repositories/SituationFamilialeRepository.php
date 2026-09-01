<?php

namespace App\Repositories;

use App\Interfaces\SituationFamilialeInterface;
use App\Models\SituationFamiliale;

class SituationFamilialeRepository extends BaseRepository implements SituationFamilialeInterface
{
    protected function model(): string
    {
        return SituationFamiliale::class;
    }

    public function findByAgent(int $agentId): ?SituationFamiliale
    {
        return SituationFamiliale::query()->where('agent_id', $agentId)->first();
    }

    public function upsertForAgent(int $agentId, array $data): SituationFamiliale
    {
        $data['agent_id'] = $agentId;

        return SituationFamiliale::query()->updateOrCreate(
            ['agent_id' => $agentId],
            $data
        );
    }
}
