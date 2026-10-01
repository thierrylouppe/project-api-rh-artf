<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface PriseEnChargeInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;
}
