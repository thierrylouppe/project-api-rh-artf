<?php

namespace App\Repositories;

use App\Interfaces\PaieElementInterface;
use App\Models\PaieElement;
use Illuminate\Support\Collection;

class PaieElementRepository extends BaseRepository implements PaieElementInterface
{
    protected function model(): string
    {
        return PaieElement::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = PaieElement::query();

        if (method_exists(PaieElement::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderBy('nature')->orderBy('libelle')->get();
    }

    public function findByCode(string $code): ?PaieElement
    {
        return PaieElement::query()->where('code', $code)->first();
    }
}
