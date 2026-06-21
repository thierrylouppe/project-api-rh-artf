<?php

namespace App\Repositories;

use App\Interfaces\ContratInterface;
use App\Models\Contrat;
use Illuminate\Support\Collection;

class ContratRepository extends BaseRepository implements ContratInterface
{
    protected function model(): string
    {
        return Contrat::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return Contrat::where('agent_id', $agentId)->get();
    }

    public function getActif(int $agentId): ?Contrat
    {
        return Contrat::where('agent_id', $agentId)
            ->where('statut', 'actif')
            ->latest()
            ->first();
    }

    public function resilier(int $id): Contrat
    {
        $contrat = $this->findById($id);
        $contrat->update(['statut' => 'resilie']);

        return $contrat->fresh();
    }
}
