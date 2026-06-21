<?php

namespace App\Interfaces;

use App\Models\Agent;
use Illuminate\Support\Collection;

interface AgentInterface extends BaseInterface
{
    public function findByMatricule(string $matricule): ?Agent;

    public function getByStatut(string $statut): Collection;

    public function assignerMatricule(int $agentId, string $matricule): Agent;
}
