<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface RemiseMaterielInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;
}
