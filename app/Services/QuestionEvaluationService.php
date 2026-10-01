<?php

namespace App\Services;

use App\Interfaces\QuestionEvaluationInterface;
use Illuminate\Support\Collection;

/**
 * Service léger pour la grille de critères (référentiel RH-paramétrable).
 * La logique métier de validation des notes est dans NoteCalculationService.
 */
class QuestionEvaluationService extends BaseService
{
    public function __construct(QuestionEvaluationInterface $repository)
    {
        parent::__construct($repository);
    }

    /** Retourne toutes les questions actives, triées par ordre. */
    public function getActives(): Collection
    {
        /** @var QuestionEvaluationInterface $repo */
        $repo = $this->repository;

        return $repo->getActives();
    }

    /** Somme des barèmes actifs (doit = 20 pour que le module soit cohérent). */
    public function sommeBaremes(): float
    {
        /** @var QuestionEvaluationInterface $repo */
        $repo = $this->repository;

        return $repo->sommeBaremes();
    }
}
