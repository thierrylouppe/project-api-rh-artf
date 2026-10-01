<?php

namespace App\Repositories;

use App\Enums\StatutReclassement;
use App\Interfaces\ReclassementInterface;
use App\Models\Reclassement;
use Illuminate\Support\Collection;

class ReclassementRepository extends BaseRepository implements ReclassementInterface
{
    protected function model(): string
    {
        return Reclassement::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return Reclassement::query()
            ->with(['agent', 'classeOrigine.grade', 'classeOrigine.categorie', 'classeCible.grade', 'classeCible.categorie', 'fonctionCible', 'diplome'])
            ->filter($filters)
            ->orderByDesc('created_at')
            ->get();
    }

    public function parAgent(int $agentId): Collection
    {
        return Reclassement::query()
            ->with(['classeOrigine.grade', 'classeOrigine.categorie', 'classeCible.grade', 'classeCible.categorie', 'fonctionCible', 'diplome'])
            ->where('agent_id', $agentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function enCoursPourAgent(int $agentId): Collection
    {
        return Reclassement::query()
            ->where('agent_id', $agentId)
            ->whereIn('statut', [StatutReclassement::SOUMIS, StatutReclassement::APPROUVE])
            ->get();
    }
}
