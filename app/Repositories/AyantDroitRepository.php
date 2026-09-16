<?php

namespace App\Repositories;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\TypeAyantDroit;
use App\Interfaces\AyantDroitInterface;
use App\Models\AyantDroit;
use Illuminate\Support\Collection;

class AyantDroitRepository extends BaseRepository implements AyantDroitInterface
{
    protected function model(): string
    {
        return AyantDroit::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = AyantDroit::query()
            ->with(['agent:id,matricule,nom,prenom,statut', 'pieces']);

        if (method_exists(AyantDroit::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderBy('nom')->orderBy('prenom')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return AyantDroit::query()
            ->where('agent_id', $agentId)
            ->with('pieces')
            ->orderByRaw("CASE WHEN type = 'conjoint' THEN 0 ELSE 1 END")
            ->orderBy('date_naissance')
            ->get();
    }

    public function findConjointActif(int $agentId, ?int $excludeId = null): ?AyantDroit
    {
        return AyantDroit::query()
            ->where('agent_id', $agentId)
            ->where('type', TypeAyantDroit::CONJOINT)
            ->where('actif', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }

    public function countTutelleActives(int $agentId, ?int $excludeId = null): int
    {
        return AyantDroit::query()
            ->where('agent_id', $agentId)
            ->where('type', TypeAyantDroit::ENFANT)
            ->where('lien_juridique', LienJuridiqueAyantDroit::TUTELLE)
            ->where('actif', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->count();
    }

    public function existsEnfantForAgent(int $agentId): bool
    {
        return AyantDroit::query()
            ->where('agent_id', $agentId)
            ->where('type', TypeAyantDroit::ENFANT)
            ->exists();
    }

    public function getActifsGroupesParAgent(): Collection
    {
        return AyantDroit::query()
            ->where('actif', true)
            ->orderByRaw("CASE WHEN type = 'conjoint' THEN 0 ELSE 1 END")
            ->orderBy('date_naissance')
            ->get()
            ->groupBy('agent_id');
    }
}
