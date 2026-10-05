<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface AbsenceInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getEnAttente(): Collection;

    public function chevauchements(int $agentId, string $debut, string $fin, ?int $exclureId = null): Collection;

    /** Somme des jours d'absences validées et non justifiées qui chevauchent [debut, fin]. */
    public function sommeJoursNonJustifiesEntre(int $agentId, string $debut, string $fin): int;
}
