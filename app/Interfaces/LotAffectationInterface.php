<?php

namespace App\Interfaces;

use App\Models\LotAffectation;

interface LotAffectationInterface extends BaseInterface
{
    public function findWithLignes(int $id): LotAffectation;
}
