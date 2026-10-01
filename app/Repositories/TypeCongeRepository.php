<?php

namespace App\Repositories;

use App\Interfaces\TypeCongeInterface;
use App\Models\TypeConge;
use Illuminate\Support\Collection;

class TypeCongeRepository extends BaseRepository implements TypeCongeInterface
{
    protected function model(): string
    {
        return TypeConge::class;
    }

    public function getDebitantSolde(): Collection
    {
        return TypeConge::query()->where('debite_solde', true)->orderBy('nom')->get();
    }
}
