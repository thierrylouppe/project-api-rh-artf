<?php

namespace App\Repositories;

use App\Interfaces\TypeSanctionInterface;
use App\Models\TypeSanction;
use Illuminate\Support\Collection;

class TypeSanctionRepository extends BaseRepository implements TypeSanctionInterface
{
    protected function model(): string
    {
        return TypeSanction::class;
    }

    public function getAll(array $filters = []): Collection
    {
        if (! array_key_exists('actif', $filters)) {
            $filters['actif'] = true;
        } elseif ($filters['actif'] === 'all') {
            unset($filters['actif']);
        }

        $query = TypeSanction::query();

        if (method_exists(TypeSanction::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderBy('nom')->get();
    }
}
