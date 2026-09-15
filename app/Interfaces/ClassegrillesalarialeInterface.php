<?php

namespace App\Interfaces;

use App\Models\Classegrillesalariale;

interface ClassegrillesalarialeInterface extends BaseInterface
{
    public function findByCategorieAndGrade(int $categorieId, int $gradeId): ?Classegrillesalariale;

    public function findByGradeNom(string $nom): ?Classegrillesalariale;
}
