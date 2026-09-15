<?php

namespace App\Repositories;

use App\Interfaces\OrganismeSocialInterface;
use App\Models\OrganismeSocial;
use Illuminate\Support\Collection;

class OrganismeSocialRepository extends BaseRepository implements OrganismeSocialInterface
{
    protected function model(): string
    {
        return OrganismeSocial::class;
    }

    public function getAll(array $filters = []): Collection
    {
        if (! array_key_exists('actif', $filters)) {
            $filters['actif'] = true;
        } elseif ($filters['actif'] === 'all') {
            unset($filters['actif']);
        }

        $query = OrganismeSocial::query();

        if (method_exists(OrganismeSocial::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderBy('nom')->get();
    }

    public function existsByNom(string $nom, ?int $excludeId = null): bool
    {
        return OrganismeSocial::query()
            ->where('nom', $nom)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
