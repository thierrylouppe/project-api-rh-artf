<?php

namespace App\Interfaces;

use App\Models\RegleAcquisitionConge;

interface RegleAcquisitionCongeInterface extends BaseInterface
{
    public function findByTypeConge(int $typeCongeId): ?RegleAcquisitionConge;
}
