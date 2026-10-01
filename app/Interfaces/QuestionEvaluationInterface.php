<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface QuestionEvaluationInterface extends BaseInterface
{
    /** Retourne toutes les questions actives, triées par ordre. */
    public function getActives(): Collection;

    /** Somme des barèmes de toutes les questions actives (doit = 20). */
    public function sommeBaremes(): float;
}
