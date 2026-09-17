<?php

namespace App\Repositories;

use App\Interfaces\PriseEnChargeInterface;
use App\Models\PriseEnCharge;
use Illuminate\Support\Collection;

class PriseEnChargeRepository extends BaseRepository implements PriseEnChargeInterface
{
    protected function model(): string
    {
        return PriseEnCharge::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = PriseEnCharge::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut',
                'structure:id,nom,type',
                'ayantDroit:id,nom,prenom,type',
            ]);

        if (method_exists(PriseEnCharge::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return PriseEnCharge::query()
            ->where('agent_id', $agentId)
            ->with(['structure:id,nom,type', 'ayantDroit:id,nom,prenom,type'])
            ->orderByDesc('created_at')
            ->get();
    }
}
