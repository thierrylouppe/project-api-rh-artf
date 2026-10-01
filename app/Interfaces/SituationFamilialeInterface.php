<?php

namespace App\Interfaces;

use App\Models\SituationFamiliale;

interface SituationFamilialeInterface extends BaseInterface
{
    public function findByAgent(int $agentId): ?SituationFamiliale;

    public function upsertForAgent(int $agentId, array $data): SituationFamiliale;
}
