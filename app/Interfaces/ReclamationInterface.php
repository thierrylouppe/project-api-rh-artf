<?php

namespace App\Interfaces;

use App\Models\Reclamation;

interface ReclamationInterface extends BaseInterface
{
    /** Réclamation liée à une fiche (max 1 par fiche). */
    public function trouverParEvaluation(int $evaluationId): ?Reclamation;
}
