<?php

namespace App\Repositories;

use App\Enums\TypeVisiteMedicale;
use App\Interfaces\VisiteMedicaleInterface;
use App\Models\VisiteMedicale;
use Illuminate\Support\Collection;

class VisiteMedicaleRepository extends BaseRepository implements VisiteMedicaleInterface
{
    protected function model(): string
    {
        return VisiteMedicale::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = VisiteMedicale::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut',
                'structure:id,nom,type',
            ]);

        if (method_exists(VisiteMedicale::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('date_visite')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return VisiteMedicale::query()
            ->where('agent_id', $agentId)
            ->with('structure:id,nom,type')
            ->orderByDesc('date_visite')
            ->get();
    }

    public function idsAgentsAvecVisiteAnnuelle(int $annee): array
    {
        return VisiteMedicale::query()
            ->where('type', TypeVisiteMedicale::ANNUELLE->value)
            ->whereYear('date_visite', $annee)
            ->pluck('agent_id')
            ->unique()
            ->values()
            ->all();
    }
}
