<?php

namespace App\Repositories;

use App\Interfaces\MotifAdministratifInterface;
use App\Models\MotifAdministratif;

class MotifAdministratifRepository extends BaseRepository implements MotifAdministratifInterface
{
    protected function model(): string
    {
        return MotifAdministratif::class;
    }
}
