<?php

namespace App\Repositories;

use App\Interfaces\PalierAncienneteCongeInterface;
use App\Models\PalierAncienneteConge;
use Illuminate\Support\Collection;

class PalierAncienneteCongeRepository extends BaseRepository implements PalierAncienneteCongeInterface
{
    protected function model(): string
    {
        return PalierAncienneteConge::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = PalierAncienneteConge::query()->orderBy('anciennete_min');

        if (method_exists(PalierAncienneteConge::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function trouverPour(int $annees): ?PalierAncienneteConge
    {
        return PalierAncienneteConge::query()
            ->where('anciennete_min', '<=', $annees)
            ->where(function ($query) use ($annees) {
                $query->whereNull('anciennete_max')
                    ->orWhere('anciennete_max', '>=', $annees);
            })
            ->orderByDesc('anciennete_min')
            ->first();
    }
}
