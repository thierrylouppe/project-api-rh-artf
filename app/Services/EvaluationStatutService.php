<?php

namespace App\Services;

use App\Enums\StatutEvaluation;
use App\Interfaces\EvaluationInterface;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Gère les transitions de statut d'une fiche d'évaluation :
 *   – signature du notateur (N+1)
 *   – signature de l'évalué (agent)
 *   – validation ou rejet par la RH
 *   – annulation par la RH
 *
 * Les transitions impliquant la réclamation (Phase 2) et
 * les avis hiérarchiques séquentiels (Phase 3) sont prévus ici
 * mais implémentés ultérieurement.
 *
 * CCN ARTF art. 63–70.
 */
class EvaluationStatutService
{
    public function __construct(
        private readonly EvaluationInterface $evaluationRepository,
    ) {}

    // ----------------------------------------------------------------
    // Actions Phase 1
    // ----------------------------------------------------------------

    /**
     * Le notateur signe la fiche (art. 63).
     * Prérequis : statut = NOTEE.
     *
     * @throws ValidationException
     */
    public function signerEvaluateur(int $evaluationId): Evaluation
    {
        $evaluation = $this->evaluer($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::SIGNEE_EVALUATEUR);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'                   => StatutEvaluation::SIGNEE_EVALUATEUR->value,
            'signe_par_evaluateur_at'  => now(),
        ]);
    }

    /**
     * L'agent signe sa fiche (art. 63).
     * Prérequis : statut = SIGNEE_EVALUATEUR.
     * Phase 2 : s'il refuse, il dépose une réclamation (appel à signerEvalue avec réclamation).
     *
     * @throws ValidationException
     */
    public function signerEvalue(int $evaluationId): Evaluation
    {
        $evaluation = $this->evaluer($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::SIGNEE_EVALUE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'              => StatutEvaluation::SIGNEE_EVALUE->value,
            'signe_par_evalue_at' => now(),
        ]);
    }

    /**
     * Remontée vers la RH pour validation de conformité (art. 66-70).
     * Prérequis : statut = SIGNEE_EVALUE ou EN_RECLAMATION.
     *
     * @throws ValidationException
     */
    public function envoyerEnValidationRh(int $evaluationId): Evaluation
    {
        $evaluation = $this->evaluer($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::EN_VALIDATION_RH);

        return $this->evaluationRepository->update($evaluationId, [
            'statut' => StatutEvaluation::EN_VALIDATION_RH->value,
        ]);
    }

    /**
     * La RH valide la fiche conforme (art. 66).
     *
     * @throws ValidationException
     */
    public function validerRh(int $evaluationId, User $rh, ?string $commentaire = null): Evaluation
    {
        $evaluation = $this->evaluer($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::FINALISEE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'              => StatutEvaluation::FINALISEE->value,
            'conforme_rh'         => true,
            'validateur_rh_id'    => $rh->id,
            'commentaire_rh'      => $commentaire,
            'date_validation_rh'  => now(),
        ]);
    }

    /**
     * La RH rejette la fiche (retour au notateur pour correction).
     *
     * @throws ValidationException
     */
    public function rejeterRh(int $evaluationId, User $rh, ?string $commentaire = null): Evaluation
    {
        $evaluation = $this->evaluer($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::REJETEE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'           => StatutEvaluation::REJETEE->value,
            'conforme_rh'      => false,
            'validateur_rh_id' => $rh->id,
            'commentaire_rh'   => $commentaire,
            // on remet à null pour permettre une re-notation
            'date_validation_rh' => now(),
        ]);
    }

    /**
     * La RH annule la fiche (hors circuit normal).
     *
     * @throws ValidationException
     */
    public function annuler(int $evaluationId, User $rh, ?string $commentaire = null): Evaluation
    {
        $evaluation = $this->evaluer($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::ANNULEE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'           => StatutEvaluation::ANNULEE->value,
            'validateur_rh_id' => $rh->id,
            'commentaire_rh'   => $commentaire,
        ]);
    }

    // ----------------------------------------------------------------
    // Helpers privés
    // ----------------------------------------------------------------

    private function evaluer(int $id): Evaluation
    {
        return $this->evaluationRepository->findById($id);
    }

    /** @throws ValidationException */
    private function assertPeutTransitionner(Evaluation $evaluation, StatutEvaluation $cible): void
    {
        if (! $evaluation->statut->peutTransitionnerVers($cible)) {
            throw ValidationException::withMessages([
                'statut' => sprintf(
                    'Impossible de passer de "%s" à "%s".',
                    $evaluation->statut->label(),
                    $cible->label(),
                ),
            ]);
        }
    }
}
