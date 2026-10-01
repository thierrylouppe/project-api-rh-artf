<?php

namespace App\Interfaces;

use App\Models\NoteEvaluation;
use Illuminate\Support\Collection;

interface NoteEvaluationInterface extends BaseInterface
{
    /** Toutes les notes d'une fiche. */
    public function getByEvaluation(int $evaluationId): Collection;

    /**
     * Upsert une note (crée ou met à jour).
     * Retourne la note résultante.
     */
    public function upsert(int $evaluationId, int $questionId, float $noteObtenue, ?string $commentaire): NoteEvaluation;
}
