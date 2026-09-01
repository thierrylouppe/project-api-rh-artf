<?php

namespace App\Repositories;

use App\Interfaces\InformationsProfessionnelleInterface;
use App\Models\InformationsProfessionnelle;

class InformationsProfessionnelleRepository extends BaseRepository implements InformationsProfessionnelleInterface
{
    protected function model(): string
    {
        return InformationsProfessionnelle::class;
    }

    public function findByAgent(int $agentId): ?InformationsProfessionnelle
    {
        return InformationsProfessionnelle::query()->where('agent_id', $agentId)->with('diplome')->first();
    }

    public function upsertForAgent(int $agentId, array $data): InformationsProfessionnelle
    {
        $data['agent_id'] = $agentId;

        $model = InformationsProfessionnelle::query()->updateOrCreate(
            ['agent_id' => $agentId],
            $data
        );

        return $model->fresh('diplome');
    }
}
