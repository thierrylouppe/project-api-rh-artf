<?php

namespace App\Repositories;

use App\Interfaces\DiplomeInterface;
use App\Models\Diplome;

class DiplomeRepository extends BaseRepository implements DiplomeInterface
{
    protected function model(): string
    {
        return Diplome::class;
    }
}
