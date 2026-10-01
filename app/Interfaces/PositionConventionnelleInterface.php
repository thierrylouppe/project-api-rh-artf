<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface PositionConventionnelleInterface extends BaseInterface
{
    public function parAgent(int $agentId): Collection;

    public function existeEnCoursPourAgent(int $agentId): bool;

    public function getActivesEcheantLe(string $date): Collection;
}
