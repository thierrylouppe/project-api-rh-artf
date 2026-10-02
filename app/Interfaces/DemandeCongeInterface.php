<?php

namespace App\Interfaces;

use App\Models\DemandeConge;
use Illuminate\Support\Collection;

interface DemandeCongeInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    public function getEnAttenteValidation(): Collection;

    public function chevauchements(int $agentId, string $debut, string $fin, ?int $exclureId = null): Collection;

    /** Agents ayant une proposition de campagne non annulée. */
    public function agentIdsPourCampagne(int $campagneId): Collection;
}
