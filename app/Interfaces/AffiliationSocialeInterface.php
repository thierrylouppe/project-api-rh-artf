<?php

namespace App\Interfaces;

use App\Models\AffiliationSociale;
use Illuminate\Support\Collection;

interface AffiliationSocialeInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function findActiveForAgentAndOrganisme(int $agentId, int $organismeId, ?int $excludeId = null): ?AffiliationSociale;

    public function agentsSansAffiliationCnss(): Collection;
}
