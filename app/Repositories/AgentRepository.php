<?php

namespace App\Repositories;

use App\Interfaces\AgentInterface;
use App\Models\Agent;
use Illuminate\Support\Collection;

class AgentRepository extends BaseRepository implements AgentInterface
{
    protected function model(): string
    {
        return Agent::class;
    }

    public function findByMatricule(string $matricule): ?Agent
    {
        return Agent::where('matricule', $matricule)->first();
    }

    public function getByStatut(string $statut): Collection
    {
        return Agent::where('statut', $statut)->get();
    }

    public function assignerMatricule(int $agentId, string $matricule): Agent
    {
        $agent = $this->findById($agentId);
        $agent->update(['matricule' => $matricule]);

        return $agent->fresh();
    }
}
