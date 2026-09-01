<?php

namespace App\Repositories;

use App\Interfaces\CongeSoldeInterface;
use App\Models\CongeSolde;
use Illuminate\Support\Collection;

class CongeSoldeRepository extends BaseRepository implements CongeSoldeInterface
{
    protected function model(): string
    {
        return CongeSolde::class;
    }

    public function findFor(int $agentId, int $typeCongeId, int $annee): ?CongeSolde
    {
        return CongeSolde::query()
            ->where('agent_id', $agentId)
            ->where('type_conge_id', $typeCongeId)
            ->where('annee', $annee)
            ->first();
    }

    public function getByAgent(int $agentId, ?int $annee = null): Collection
    {
        $query = CongeSolde::query()->where('agent_id', $agentId)->with('typeConge');

        if ($annee !== null) {
            $query->where('annee', $annee);
        }

        return $query->orderByDesc('annee')->get();
    }
}
