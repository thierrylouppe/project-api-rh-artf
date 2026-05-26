<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface BureauInterface extends BaseInterface
{
    public function getByService(int $serviceId): Collection;
}
