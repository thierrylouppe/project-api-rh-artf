<?php

namespace App\Interfaces;

use App\Models\SessionEvaluation;
use Illuminate\Database\Eloquent\Model;

interface SessionEvaluationInterface extends BaseInterface
{
    /** Retourne la session actuellement ouverte, ou null. */
    public function trouverOuverte(): ?SessionEvaluation;
}
