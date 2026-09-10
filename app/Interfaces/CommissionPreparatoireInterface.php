<?php

namespace App\Interfaces;

use App\Models\CommissionPreparatoire;

interface CommissionPreparatoireInterface extends BaseInterface
{
    public function trouverParSession(int $sessionId): ?CommissionPreparatoire;
}
