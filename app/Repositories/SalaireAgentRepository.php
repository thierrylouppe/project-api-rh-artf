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
        return SalaireAgent::with(['agent.fonction', 'agent.nominationActive', 'salaire', 'classe.categorie', 'classe.grade'])
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

    public function existsPourAgent(int $agentId): bool
    {
        return SalaireAgent::query()->where('agent_id', $agentId)->exists();
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

    public function getCouvrantPeriode(string $debut, string $fin): Collection
    {
        return SalaireAgent::with(['salaire', 'classe.categorie', 'classe.grade'])
            ->where('date_debut', '<=', $fin)
            ->where(function ($q) use ($debut) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $debut);
            })
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get()
            ->unique('agent_id')
            ->keyBy('agent_id');
    }
}
