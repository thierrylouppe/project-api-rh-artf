<?php

namespace App\Repositories;

use App\Interfaces\TypeContratInterface;
use App\Models\TypeContrat;

class TypeContratRepository extends BaseRepository implements TypeContratInterface
{
    protected function model(): string
    {
        return TypeContrat::class;
    }
}
