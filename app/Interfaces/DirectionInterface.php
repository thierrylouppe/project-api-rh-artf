<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface DirectionInterface extends BaseInterface
{
    public function getByAdministration(int $administrationId): Collection;
}
