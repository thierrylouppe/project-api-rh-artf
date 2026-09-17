<?php

namespace App\Interfaces;

use App\Models\PaieLot;
use App\Models\SessionEvaluation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReportingInterface
{
    /** Agents filtrés (structure / statut), relations dashboard chargées. */
    public function agents(array $filters = [], bool $presentOnly = false): Collection;

    public function paginerAgents(array $filters = [], bool $presentOnly = true): LengthAwarePaginator;

    public function demandesConge(int $annee): Collection;

    public function absences(int $annee): Collection;

    public function evaluations(int $annee): Collection;

    public function sessions(int $annee): Collection;

    public function sessionCourante(?int $sessionId = null): ?SessionEvaluation;

    public function evaluationsDeSession(int $sessionId): Collection;

    public function agentsSansN1(): Collection;

    public function dossiersIncomplets(): Collection;

    public function contratsEcheance(int $jours): Collection;

    public function dernierLotCloture(): ?PaieLot;
}
