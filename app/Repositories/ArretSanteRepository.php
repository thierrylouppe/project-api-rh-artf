<?php

namespace App\Repositories;

use App\Interfaces\ArretSanteInterface;
use App\Models\ArretSante;
use Illuminate\Support\Collection;

class ArretSanteRepository extends BaseRepository implements ArretSanteInterface
{
    protected function model(): string
    {
        return ArretSante::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = ArretSante::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut',
                'structure:id,nom,type',
            ]);

        if (method_exists(ArretSante::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return ArretSante::query()
            ->where('agent_id', $agentId)
            ->with('structure:id,nom,type')
            ->orderByDesc('created_at')
            ->get();
    }
}
