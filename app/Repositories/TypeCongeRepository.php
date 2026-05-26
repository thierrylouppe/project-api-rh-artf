<?php

namespace App\Repositories;

use App\Interfaces\TypeCongeInterface;
use App\Models\TypeConge;

class TypeCongeRepository extends BaseRepository implements TypeCongeInterface
{
    protected function model(): string
    {
        return TypeConge::class;
    }
}
