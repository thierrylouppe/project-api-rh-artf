<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface AbsenceInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;
}
