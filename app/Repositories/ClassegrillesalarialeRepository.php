<?php

namespace App\Repositories;

use App\Interfaces\ClassegrillesalarialeInterface;
use App\Models\Classegrillesalariale;
use Illuminate\Support\Collection;

class ClassegrillesalarialeRepository extends BaseRepository implements ClassegrillesalarialeInterface
{
    protected function model(): string
    {
        return Classegrillesalariale::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return Classegrillesalariale::with(['categorie', 'grade'])
            ->filter($filters)
            ->get();
    }

    public function findByCategorieAndGrade(int $categorieId, int $gradeId): ?Classegrillesalariale
    {
        return Classegrillesalariale::where('categorie_id', $categorieId)
            ->where('grade_id', $gradeId)
            ->first();
    }
}
