<?php

namespace App\Interfaces;

use App\Models\CongeSolde;
use Illuminate\Support\Collection;

interface CongeSoldeInterface extends BaseInterface
{
    public function findFor(int $agentId, int $typeCongeId, int $annee): ?CongeSolde;

    public function getByAgent(int $agentId, ?int $annee = null): Collection;
}
