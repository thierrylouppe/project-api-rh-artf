<?php

namespace App\Repositories;

use App\Interfaces\TypeAbsenceInterface;
use App\Models\TypeAbsence;

class TypeAbsenceRepository extends BaseRepository implements TypeAbsenceInterface
{
    protected function model(): string
    {
        return TypeAbsence::class;
    }
}
