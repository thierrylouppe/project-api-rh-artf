<?php

namespace App\Interfaces;

use App\Models\Agent;
use Illuminate\Support\Collection;

interface AgentInterface extends BaseInterface
{
    public function findByMatricule(string $matricule): ?Agent;

    public function getByStatut(string $statut): Collection;

    public function assignerMatricule(int $agentId, string $matricule): Agent;

    public function modifierMatricule(int $agentId, string $nouveauMatricule): Agent;

    /** Vérifie si un matricule est déjà utilisé par un autre agent. */
    public function matriculeEstPris(string $matricule, int $excludeAgentId): bool;
}
