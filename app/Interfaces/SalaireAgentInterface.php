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

    public function cloturerActifs(int $agentId, string $dateFin): void;
}
