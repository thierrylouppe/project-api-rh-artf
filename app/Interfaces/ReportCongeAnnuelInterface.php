<?php

namespace App\Interfaces;

use App\Models\ReportCongeAnnuel;

interface ReportCongeAnnuelInterface extends BaseInterface
{
    public function findActif(int $agentId, int $anneeSource): ?ReportCongeAnnuel;
}
