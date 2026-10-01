<?php

namespace App\Interfaces;

use App\Models\PalierAncienneteConge;

interface PalierAncienneteCongeInterface extends BaseInterface
{
    public function trouverPour(int $annees): ?PalierAncienneteConge;
}
