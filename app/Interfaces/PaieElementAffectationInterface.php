<?php

namespace App\Interfaces;

use App\Models\PaieElementAffectation;
use Illuminate\Support\Collection;

interface PaieElementAffectationInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function findChevauchement(
        int $agentId,
        int $elementId,
        string $dateDebut,
        ?string $dateFin,
        ?int $excludeId = null,
    ): ?PaieElementAffectation;

    public function existsByElement(int $elementId): bool;

    /** Affectations dont la période chevauche [debut, fin]. */
    public function getCouvrantPeriode(int $agentId, string $debut, string $fin): Collection;
}
