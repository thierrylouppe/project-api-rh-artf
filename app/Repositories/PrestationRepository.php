<?php

namespace App\Repositories;

use App\Interfaces\PrestationInterface;
use App\Models\Prestation;
use Illuminate\Support\Collection;

class PrestationRepository extends BaseRepository implements PrestationInterface
{
    protected function model(): string
    {
        return Prestation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Prestation::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut',
                'ayantDroit:id,nom,prenom,type',
                'createur:id,name',
            ]);

        if (method_exists(Prestation::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return Prestation::query()
            ->where('agent_id', $agentId)
            ->with(['ayantDroit:id,nom,prenom,type', 'createur:id,name'])
            ->orderByDesc('created_at')
            ->get();
    }
}
