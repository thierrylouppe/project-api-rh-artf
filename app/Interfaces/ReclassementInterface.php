<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ReclassementInterface extends BaseInterface
{
    public function parAgent(int $agentId): Collection;

    public function enCoursPourAgent(int $agentId): Collection;
}
