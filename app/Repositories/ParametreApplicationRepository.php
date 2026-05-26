<?php

namespace App\Repositories;

use App\Interfaces\ParametreApplicationInterface;
use App\Models\ParametreApplication;

class ParametreApplicationRepository extends BaseRepository implements ParametreApplicationInterface
{
    protected function model(): string
    {
        return ParametreApplication::class;
    }

    public function findByCle(string $cle): ?ParametreApplication
    {
        return ParametreApplication::where('cle', $cle)->first();
    }
}
