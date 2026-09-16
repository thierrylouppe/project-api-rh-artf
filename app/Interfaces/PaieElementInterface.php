<?php

namespace App\Interfaces;

use App\Models\PaieElement;

interface PaieElementInterface extends BaseInterface
{
    public function findByCode(string $code): ?PaieElement;
}
