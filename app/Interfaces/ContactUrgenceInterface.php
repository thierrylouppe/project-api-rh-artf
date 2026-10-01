<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ContactUrgenceInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;
}
