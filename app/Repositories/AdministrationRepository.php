<?php

namespace App\Repositories;

use App\Interfaces\AdministrationInterface;
use App\Models\Administration;
use Illuminate\Support\Collection;

class AdministrationRepository extends BaseRepository implements AdministrationInterface
{
    protected function model(): string
    {
        return Administration::class;
    }

    public function getByLocalite(int $localiteId): Collection
    {
        return Administration::where('localite_id', $localiteId)->get();
    }
}
