<?php

namespace App\Interfaces;

use App\Models\InformationsPersonnelle;

interface InformationsPersonnelleInterface extends BaseInterface
{
    public function findByAgent(int $agentId): ?InformationsPersonnelle;

    public function upsertForAgent(int $agentId, array $data): InformationsPersonnelle;
}
