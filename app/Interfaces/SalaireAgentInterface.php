<?php

namespace App\Interfaces;

use App\Models\SalaireAgent;
use Illuminate\Support\Collection;

interface SalaireAgentInterface extends BaseInterface
{
    public function getByAgent(int $agentId): Collection;

    /** Chronologique croissant (timeline carrière). */
    public function getHistoriqueByAgent(int $agentId): Collection;

    public function getActuel(int $agentId): ?SalaireAgent;

    public function existsPourAgent(int $agentId): bool;

    public function cloturerActifs(int $agentId, string $dateFin): void;

    /** Salaires dont la période chevauche [debut, fin], un par agent (le plus récent). */
    public function getCouvrantPeriode(string $debut, string $fin): Collection;
}
