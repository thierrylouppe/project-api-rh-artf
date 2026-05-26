<?php

namespace App\Repositories;

use App\Interfaces\GradeInterface;
use App\Models\Grade;

class GradeRepository extends BaseRepository implements GradeInterface
{
    protected function model(): string
    {
        return Grade::class;
    }
}
