<?php

namespace App\Interfaces;

use App\Enums\StatutAffectation;
use App\Models\Affectation;
use Illuminate\Support\Collection;

interface AffectationInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getActive(int $agentId): ?Affectation;

    public function terminer(int $id, ?string $dateFin): Affectation;

    /**
     * Remonte Bureau → Service → Direction pour trouver l'agent d'une nomination active.
     * $saufAgentId est écarté (un chef n'est pas son propre supérieur).
     */
    public function resoudreSuperiorParStructure(string $structurableType, int $structurableId, ?int $saufAgentId = null): ?int;

    /** Affectations actives, avec agent, fonction et structure parente. */
    public function getActivesPourHierarchie(): Collection;

    /** Agents dont l'affectation active désigne ce supérieur. */
    public function getActivesParSuperieur(int $superieurId): Collection;

    /**
     * Affectations active/terminée d'un agent qui chevauchent [debut, fin).
     *
     * @param  \DateTimeInterface|string  $debut
     * @param  \DateTimeInterface|string  $fin
     */
    public function getPourAgentSurPeriode(int $agentId, $debut, $fin): Collection;

    public function getByLot(int $lotId): Collection;

    public function updateStatutByLot(int $lotId, StatutAffectation $statut): void;
}
