<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface VisiteMedicaleInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    /** @return list<int> */
    public function idsAgentsAvecVisiteAnnuelle(int $annee): array;
}
