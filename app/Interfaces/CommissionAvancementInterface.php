<?php

namespace App\Interfaces;

use App\Models\CommissionAvancement;

interface CommissionAvancementInterface extends BaseInterface
{
    public function trouverParSession(int $sessionId): ?CommissionAvancement;
}
