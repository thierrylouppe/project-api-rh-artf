<?php

namespace App\Interfaces;

use App\Models\Echelon;

interface EchelonInterface extends BaseInterface
{
    public function findByNumero(int $numero): ?Echelon;
}
