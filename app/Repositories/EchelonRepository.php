<?php

namespace App\Repositories;

use App\Interfaces\EchelonInterface;
use App\Models\Echelon;

class EchelonRepository extends BaseRepository implements EchelonInterface
{
    protected function model(): string
    {
        return Echelon::class;
    }
}
