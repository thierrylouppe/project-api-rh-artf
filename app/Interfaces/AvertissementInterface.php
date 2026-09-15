<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface AvertissementInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;
}
