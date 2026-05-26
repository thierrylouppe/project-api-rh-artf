<?php

namespace App\Repositories;

use App\Interfaces\FonctionInterface;
use App\Models\Fonction;

class FonctionRepository extends BaseRepository implements FonctionInterface
{
    protected function model(): string
    {
        return Fonction::class;
    }
}
