<?php

namespace App\Interfaces;

use App\Models\Affectation;
use Illuminate\Support\Collection;

interface AffectationInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getActive(int $agentId): ?Affectation;

    public function terminer(int $id, ?string $dateFin): Affectation;

    /** Remonte la hiérarchie (Bureau → Service → Direction) pour trouver l'agent_id du responsable actif. */
    public function resoudreSuperiorParStructure(string $structurableType, int $structurableId): ?int;
}
