<?php

namespace App\Repositories;

use App\Enums\StatutSalaireAgent;
use App\Interfaces\SalaireAgentInterface;
use App\Models\SalaireAgent;
use Illuminate\Support\Collection;

class SalaireAgentRepository extends BaseRepository implements SalaireAgentInterface
{
    protected function model(): string
    {
        return SalaireAgent::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return SalaireAgent::with(['agent', 'salaire', 'classe.categorie', 'classe.grade'])
            ->filter($filters)
            ->latest('date_debut')
            ->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return SalaireAgent::with(['salaire', 'classe.categorie', 'classe.grade'])
            ->where('agent_id', $agentId)
            ->latest('date_debut')
            ->get();
    }

    public function getHistoriqueByAgent(int $agentId): Collection
    {
        return SalaireAgent::with(['salaire', 'classe.categorie', 'classe.grade'])
            ->where('agent_id', $agentId)
            ->orderBy('date_debut')
            ->orderBy('id')
            ->get();
    }

    public function getActuel(int $agentId): ?SalaireAgent
    {
        return SalaireAgent::with(['salaire', 'classe.categorie', 'classe.grade'])
            ->where('agent_id', $agentId)
            ->where('statut', StatutSalaireAgent::ACTIF)
            ->latest('date_debut')
            ->first();
    }

    public function cloturerActifs(int $agentId, string $dateFin): void
    {
        SalaireAgent::where('agent_id', $agentId)
            ->where('statut', StatutSalaireAgent::ACTIF)
            ->update([
                'statut'   => StatutSalaireAgent::CLOTURE,
                'date_fin' => $dateFin,
            ]);
    }
}
