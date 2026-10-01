<?php

namespace App\Interfaces;

use App\Models\LotNomination;

interface LotNominationInterface extends BaseInterface
{
    public function findWithLignes(int $id): LotNomination;
}
