<?php

namespace App\Interfaces;

use App\Models\InscriptionFormation;
use Illuminate\Support\Collection;

interface InscriptionFormationInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function findOuverte(int $agentId, int $formationId, ?int $excludeId = null): ?InscriptionFormation;
}
