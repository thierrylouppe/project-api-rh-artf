<?php

namespace App\Repositories;

use App\Interfaces\AvisHierarchiqueInterface;
use App\Models\AvisHierarchique;
use Illuminate\Support\Collection;

class AvisHierarchiqueRepository extends BaseRepository implements AvisHierarchiqueInterface
{
    protected function model(): string
    {
        return AvisHierarchique::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return AvisHierarchique::query()->orderBy('ordre')->get();
    }

    public function parEvaluation(int $evaluationId): Collection
    {
        return AvisHierarchique::query()
            ->where('evaluation_id', $evaluationId)
            ->with(['signePar'])
            ->orderBy('ordre')
            ->get();
    }

    public function trouverParNiveau(int $evaluationId, string $niveau): ?AvisHierarchique
    {
        return AvisHierarchique::query()
            ->where('evaluation_id', $evaluationId)
            ->where('niveau', $niveau)
            ->first();
    }

    public function tousNiveauxSignes(int $evaluationId, array $niveauxRequis): bool
    {
        if (empty($niveauxRequis)) {
            return true;
        }

        $niveauxSignes = AvisHierarchique::query()
            ->where('evaluation_id', $evaluationId)
            ->whereIn('niveau', $niveauxRequis)
            ->where('signe', true)
            ->count();

        return $niveauxSignes === count($niveauxRequis);
    }
}
