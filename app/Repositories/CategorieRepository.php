<?php

namespace App\Repositories;

use App\Interfaces\CategorieInterface;
use App\Models\Categorie;

class CategorieRepository extends BaseRepository implements CategorieInterface
{
    protected function model(): string
    {
        return Categorie::class;
    }
}
