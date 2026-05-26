<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface ServiceInterface extends BaseInterface
{
    public function getByDirection(int $directionId): Collection;
}
