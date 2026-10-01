<?php

namespace App\Services;

use App\Enums\MentionEvaluation;
use App\Enums\StatutEvaluation;
use App\Interfaces\EvaluationInterface;
use App\Interfaces\NoteEvaluationInterface;
use App\Interfaces\QuestionEvaluationInterface;
use App\Models\Evaluation;
use App\Models\NoteEvaluation;
use Illuminate\Validation\ValidationException;

/**
 * Saisie et calcul des notes.
 *
 * Responsabilités :
 *   – valider note_obtenue ≤ bareme_max
 *   – upsert la note (updateOrCreate)
 *   – recalculer note_globale + mention
 *   – mettre à jour le statut de la fiche (en_attente → en_cours → notee)
 *
 * CCN ARTF art. 63.
 */
class NoteCalculationService
{
    public function __construct(
        private readonly EvaluationInterface       $evaluationRepository,
        private readonly NoteEvaluationInterface   $noteRepository,
        private readonly QuestionEvaluationInterface $questionRepository,
    ) {}

    /**
     * Saisit ou modifie la note d'un critère sur une fiche.
     *
     * @throws ValidationException
     */
    public function noter(int $evaluationId, int $questionId, float $noteObtenue, ?string $commentaire = null): Evaluation
    {
        /** @var Evaluation $evaluation */
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        if ($evaluation->statut->estTerminee()) {
            throw ValidationException::withMessages([
                'statut' => "La fiche est {$evaluation->statut->label()} et ne peut plus être modifiée.",
            ]);
        }

        // Interdit de noter une fiche déjà signée par l'évaluateur
        if (in_array($evaluation->statut, [
            StatutEvaluation::SIGNEE_EVALUATEUR,
            StatutEvaluation::SIGNEE_EVALUE,
            StatutEvaluation::EN_VALIDATION_RH,
            StatutEvaluation::FINALISEE,
        ], true)) {
            throw ValidationException::withMessages([
                'statut' => "La fiche est {$evaluation->statut->label()} : la notation n'est plus modifiable.",
            ]);
        }

        // Vérification barème
        $question = $this->questionRepository->findById($questionId);

        if (! $question->actif) {
            throw ValidationException::withMessages([
                'question_id' => 'Ce critère est désactivé et ne peut plus être noté.',
            ]);
        }

        if ($noteObtenue > $question->bareme_max) {
            throw ValidationException::withMessages([
                'note_obtenue' => "La note ({$noteObtenue}) dépasse le barème maximum ({$question->bareme_max}) du critère.",
            ]);
        }

        if ($noteObtenue < 0) {
            throw ValidationException::withMessages([
                'note_obtenue' => 'La note ne peut pas être négative.',
            ]);
        }

        // Upsert la note
        $this->noteRepository->upsert($evaluationId, $questionId, $noteObtenue, $commentaire);

        // Recalculer et persister note_globale + mention + statut
        return $this->recalculer($evaluation);
    }

    /**
     * Recalcule note_globale, mention et statut à partir des notes existantes.
     * Appelé après chaque note saisie.
     */
    public function recalculer(Evaluation $evaluation): Evaluation
    {
        $notes    = $this->noteRepository->getByEvaluation($evaluation->id);
        $actives  = $this->questionRepository->getActives();

        $noteGlobale = $notes->sum('note_obtenue');
        $estComplete = $notes->count() >= $actives->count()
            && $actives->pluck('id')->diff($notes->pluck('question_id'))->isEmpty();

        $mention = $estComplete ? MentionEvaluation::depuisNote((float) $noteGlobale)->value : null;

        // Transition de statut
        $nouveauStatut = $this->statut($evaluation->statut, $notes->count(), $estComplete);

        $this->evaluationRepository->update($evaluation->id, [
            'note_globale' => $noteGlobale,
            'mention'      => $mention,
            'statut'       => $nouveauStatut->value,
        ]);

        return $this->evaluationRepository->findById($evaluation->id);
    }

    // ----------------------------------------------------------------
    // Helpers privés
    // ----------------------------------------------------------------

    private function statut(StatutEvaluation $actuel, int $nbNotes, bool $estComplete): StatutEvaluation
    {
        // Si déjà dans un statut avancé, ne pas rétrograder
        $statutsAvances = [
            StatutEvaluation::SIGNEE_EVALUATEUR,
            StatutEvaluation::SIGNEE_EVALUE,
            StatutEvaluation::EN_RECLAMATION,
            StatutEvaluation::EN_VALIDATION_RH,
            StatutEvaluation::FINALISEE,
        ];

        if (in_array($actuel, $statutsAvances, true)) {
            return $actuel;
        }

        if ($estComplete) {
            return StatutEvaluation::NOTEE;
        }

        if ($nbNotes > 0) {
            return StatutEvaluation::EN_COURS;
        }

        return StatutEvaluation::EN_ATTENTE;
    }
}
