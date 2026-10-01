<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ArretSanteInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;
}
