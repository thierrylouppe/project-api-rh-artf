<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface AdministrationInterface extends BaseInterface
{
    public function getByLocalite(int $localiteId): Collection;
}
