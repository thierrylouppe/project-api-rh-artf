<?php

namespace App\Interfaces;

use App\Models\InformationsProfessionnelle;

interface InformationsProfessionnelleInterface extends BaseInterface
{
    public function findByAgent(int $agentId): ?InformationsProfessionnelle;

    public function upsertForAgent(int $agentId, array $data): InformationsProfessionnelle;
}
