<?php

namespace App\Repositories;

use App\Interfaces\AbsenceInterface;
use App\Models\Absence;
use Illuminate\Support\Collection;

class AbsenceRepository extends BaseRepository implements AbsenceInterface
{
    protected function model(): string
    {
        return Absence::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return Absence::query()
            ->where('agent_id', $agentId)
            ->with(['typeAbsence', 'agent'])
            ->orderByDesc('date_debut')
            ->get();
    }
}
