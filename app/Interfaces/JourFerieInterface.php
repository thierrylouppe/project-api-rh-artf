<?php

namespace App\Interfaces;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface JourFerieInterface extends BaseInterface
{
    public function datesFeriees(CarbonInterface $debut, CarbonInterface $fin): Collection;
}
