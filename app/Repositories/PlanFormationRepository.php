<?php

namespace App\Repositories;

use App\Interfaces\PlanFormationInterface;
use App\Models\PlanFormation;
use App\Models\PlanFormationLigne;
use Illuminate\Support\Collection;

class PlanFormationRepository extends BaseRepository implements PlanFormationInterface
{
    protected function model(): string
    {
        return PlanFormation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = PlanFormation::query()->with(['lignes.formation', 'createur:id,name', 'validateur:id,name']);

        if (method_exists(PlanFormation::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('annee')->get();
    }

    public function findByAnnee(int $annee): ?PlanFormation
    {
        return PlanFormation::query()->where('annee', $annee)->first();
    }

    public function getLignes(int $planId): Collection
    {
        return PlanFormationLigne::query()
            ->where('plan_id', $planId)
            ->with('formation')
            ->orderBy('id')
            ->get();
    }

    public function ajouterLigne(int $planId, array $data): PlanFormationLigne
    {
        $data['plan_id'] = $planId;

        return PlanFormationLigne::query()->create($data)->load('formation');
    }

    public function trouverLigne(int $planId, int $ligneId): PlanFormationLigne
    {
        return PlanFormationLigne::query()
            ->where('plan_id', $planId)
            ->with('formation')
            ->findOrFail($ligneId);
    }

    public function supprimerLigne(int $ligneId): bool
    {
        return (bool) PlanFormationLigne::query()->where('id', $ligneId)->delete();
    }
}
