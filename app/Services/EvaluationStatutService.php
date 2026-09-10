<?php

namespace App\Services;

use App\Enums\StatutEvaluation;
use App\Interfaces\EvaluationInterface;
use App\Interfaces\ReclamationInterface;
use App\Models\Evaluation;
use App\Models\User;
use App\Services\AvisHierarchiqueService;
use Illuminate\Validation\ValidationException;

/**
 * Gère les transitions de statut d'une fiche d'évaluation.
 *
 * Phase 1 : création fiche → notation → calcul note/mention
 * Phase 2 : avis N+1 + signature, signature agent, réclamation, validation RH
 * Phase 3 (futur) : avis hiérarchiques séquentiels
 *
 * CCN ARTF art. 63–70.
 */
class EvaluationStatutService
{
    public function __construct(
        private readonly EvaluationInterface   $evaluationRepository,
        private readonly ReclamationInterface  $reclamationRepository,
        private readonly AvisHierarchiqueService $avisService,
    ) {}

    // ----------------------------------------------------------------
    // Phase 1 : conservé tel quel (déjà livré)
    // ----------------------------------------------------------------

    /**
     * Le notateur signe la fiche SANS vérification d'avis (Phase 1 simple).
     * Prérequis : statut = NOTEE.
     * @deprecated Utiliser signerEvaluateurAvecAvis en Phase 2.
     * @throws ValidationException
     */
    public function signerEvaluateur(int $evaluationId): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);
        $this->assertPeutTransitionner($evaluation, StatutEvaluation::SIGNEE_EVALUATEUR);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'                  => StatutEvaluation::SIGNEE_EVALUATEUR->value,
            'signe_par_evaluateur_at' => now(),
        ]);
    }

    // ----------------------------------------------------------------
    // Phase 2 — Nouvelles actions
    // ----------------------------------------------------------------

    /**
     * Le notateur donne son avis ET signe la fiche en une seule action (art. 63).
     * Prérequis : statut = NOTEE + avis_superieur ≥ 10 caractères.
     *
     * @throws ValidationException
     */
    public function signerEvaluateurAvecAvis(int $evaluationId, string $avisSuperieur): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::SIGNEE_EVALUATEUR);

        if (mb_strlen(trim($avisSuperieur)) < 10) {
            throw ValidationException::withMessages([
                'avis_superieur' => 'L\'avis du notateur doit comporter au moins 10 caractères.',
            ]);
        }

        if (mb_strlen($avisSuperieur) > 2000) {
            throw ValidationException::withMessages([
                'avis_superieur' => 'L\'avis ne peut pas dépasser 2000 caractères.',
            ]);
        }

        return $this->evaluationRepository->update($evaluationId, [
            'statut'                  => StatutEvaluation::SIGNEE_EVALUATEUR->value,
            'avis_superieur'          => $avisSuperieur,
            'signe_par_evaluateur_at' => now(),
        ]);
    }

    /**
     * L'agent signe sa fiche (prise de connaissance, art. 63).
     * Prérequis : statut = SIGNEE_EVALUATEUR (le N+1 doit avoir signé en premier).
     *
     * @throws ValidationException
     */
    public function signerEvalue(int $evaluationId): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::SIGNEE_EVALUE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'              => StatutEvaluation::SIGNEE_EVALUE->value,
            'signe_par_evalue_at' => now(),
        ]);
    }

    /**
     * L'agent dépose une réclamation (art. 65).
     * Prérequis : statut = SIGNEE_EVALUE + pas de réclamation déjà ouverte.
     *
     * @throws ValidationException
     */
    public function reclamer(int $evaluationId, int $agentId, string $motif): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::EN_RECLAMATION);

        if (mb_strlen(trim($motif)) < 10) {
            throw ValidationException::withMessages([
                'motif' => 'Le motif de réclamation doit comporter au moins 10 caractères.',
            ]);
        }

        // Vérifier qu'il n'y a pas déjà une réclamation
        $existante = $this->reclamationRepository->trouverParEvaluation($evaluationId);
        if ($existante && ! $existante->statut->estTraitee()) {
            throw ValidationException::withMessages([
                'reclamation' => 'Une réclamation est déjà en cours pour cette fiche.',
            ]);
        }

        // Créer la réclamation
        $this->reclamationRepository->create([
            'evaluation_id' => $evaluationId,
            'agent_id'      => $agentId,
            'motif'         => $motif,
            'statut'        => 'en_attente',
        ]);

        return $this->evaluationRepository->update($evaluationId, [
            'statut' => StatutEvaluation::EN_RECLAMATION->value,
        ]);
    }

    /**
     * Envoi en validation RH (art. 66).
     * Phase 3 : tous les avis hiérarchiques requis doivent être signés.
     * Phase 2 : depuis SIGNEE_EVALUE (sans avis hiérarchiques → tousAvisSignes = true si liste vide).
     *
     * @throws ValidationException
     */
    public function envoyerEnValidationRh(int $evaluationId): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);

        $this->assertPeutTransitionner($evaluation, StatutEvaluation::EN_VALIDATION_RH);

        // Phase 3 : vérifier que tous les avis requis sont signés
        if (! $this->avisService->tousAvisSignes($evaluationId)) {
            throw ValidationException::withMessages([
                'avis_hierarchiques' => 'Tous les avis hiérarchiques requis doivent être signés avant de transmettre à la RH.',
            ]);
        }

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
        $evaluation = $this->findEvaluation($evaluationId);
        $this->assertPeutTransitionner($evaluation, StatutEvaluation::FINALISEE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'             => StatutEvaluation::FINALISEE->value,
            'conforme_rh'        => true,
            'validateur_rh_id'   => $rh->id,
            'commentaire_rh'     => $commentaire,
            'date_validation_rh' => now(),
        ]);
    }

    /**
     * La RH rejette la fiche (retour au notateur pour correction).
     *
     * @throws ValidationException
     */
    public function rejeterRh(int $evaluationId, User $rh, ?string $commentaire = null): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);
        $this->assertPeutTransitionner($evaluation, StatutEvaluation::REJETEE);

        return $this->evaluationRepository->update($evaluationId, [
            'statut'             => StatutEvaluation::REJETEE->value,
            'conforme_rh'        => false,
            'validateur_rh_id'   => $rh->id,
            'commentaire_rh'     => $commentaire,
            'date_validation_rh' => now(),
        ]);
    }

    /**
     * La RH annule une fiche (hors circuit normal).
     *
     * @throws ValidationException
     */
    public function annuler(int $evaluationId, User $rh, ?string $commentaire = null): Evaluation
    {
        $evaluation = $this->findEvaluation($evaluationId);
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

    private function findEvaluation(int $id): Evaluation
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
