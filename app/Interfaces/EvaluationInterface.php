<?php

namespace App\Interfaces;

use App\Models\Evaluation;
use Illuminate\Support\Collection;

interface EvaluationInterface extends BaseInterface
{
    /** Toutes les fiches d'une session. */
    public function getBySession(int $sessionId): Collection;

    /** Toutes les fiches dont le N+1 est ce supérieur. */
    public function getBySuperieur(int $superieurId): Collection;

    /** Toutes les fiches d'un agent évalué. */
    public function getByAgent(int $agentId): Collection;

    /** Trouve la fiche d'un agent pour une session précise, ou null. */
    public function trouverParAgentSession(int $agentId, int $sessionId): ?Evaluation;
}
