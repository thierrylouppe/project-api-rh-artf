<?php

namespace App\Interfaces;

use App\Models\AyantDroit;
use Illuminate\Support\Collection;

interface AyantDroitInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function findConjointActif(int $agentId, ?int $excludeId = null): ?AyantDroit;

    public function countTutelleActives(int $agentId, ?int $excludeId = null): int;

    public function existsEnfantForAgent(int $agentId): bool;

    /** Ayants droit actifs, groupés par agent_id. */
    public function getActifsGroupesParAgent(): Collection;
}
