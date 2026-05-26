<?php

namespace App\Repositories;

use App\Interfaces\LocaliteInterface;
use App\Models\Localite;

class LocaliteRepository extends BaseRepository implements LocaliteInterface
{
    protected function model(): string
    {
        return Localite::class;
    }
}
