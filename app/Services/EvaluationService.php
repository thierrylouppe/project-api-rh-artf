<?php

namespace App\Services;

use App\Interfaces\EvaluationInterface;

/**
 * Service wrapper pour les fiches d'évaluation.
 * La logique de transition est dans EvaluationStatutService.
 * La logique de notation est dans NoteCalculationService.
 */
class EvaluationService extends BaseService
{
    public function __construct(EvaluationInterface $repository)
    {
        parent::__construct($repository);
    }

    /** Fiches dont ce supérieur est le notateur. */
    public function getBySuperieur(int $superieurId): \Illuminate\Support\Collection
    {
        /** @var EvaluationInterface $repo */
        $repo = $this->repository;

        return $repo->getBySuperieur($superieurId);
    }

    /** Fiches d'un agent évalué. */
    public function getByAgent(int $agentId): \Illuminate\Support\Collection
    {
        /** @var EvaluationInterface $repo */
        $repo = $this->repository;

        return $repo->getByAgent($agentId);
    }

    /** Fiches d'une session. */
    public function getBySession(int $sessionId): \Illuminate\Support\Collection
    {
        /** @var EvaluationInterface $repo */
        $repo = $this->repository;

        return $repo->getBySession($sessionId);
    }

    /** Tableau d'avancement : fiches finalisées inscrites. */
    public function getTableau(int $sessionId): \Illuminate\Support\Collection
    {
        /** @var EvaluationInterface $repo */
        $repo = $this->repository;

        return $repo->getTableau($sessionId);
    }
}
