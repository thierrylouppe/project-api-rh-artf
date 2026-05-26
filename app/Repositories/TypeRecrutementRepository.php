<?php

namespace App\Repositories;

use App\Interfaces\TypeRecrutementInterface;
use App\Models\TypeRecrutement;

class TypeRecrutementRepository extends BaseRepository implements TypeRecrutementInterface
{
    protected function model(): string
    {
        return TypeRecrutement::class;
    }
}
