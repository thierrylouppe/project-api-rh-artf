<?php

namespace App\Interfaces;

use App\Models\ParametreApplication;

interface ParametreApplicationInterface extends BaseInterface
{
    public function findByCle(string $cle): ?ParametreApplication;
}
