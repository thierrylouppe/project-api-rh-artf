<?php

namespace App\Repositories;

use App\Interfaces\CatalogueFormationInterface;
use App\Models\CatalogueFormation;
use Illuminate\Support\Collection;

class CatalogueFormationRepository extends BaseRepository implements CatalogueFormationInterface
{
    protected function model(): string
    {
        return CatalogueFormation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        if (! array_key_exists('actif', $filters)) {
            $filters['actif'] = true;
        } elseif ($filters['actif'] === 'all') {
            unset($filters['actif']);
        }

        $query = CatalogueFormation::query();

        if (method_exists(CatalogueFormation::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderBy('titre')->get();
    }

    public function existsByTitre(string $titre, ?int $excludeId = null): bool
    {
        return CatalogueFormation::query()
            ->where('titre', $titre)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
