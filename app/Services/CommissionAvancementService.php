<?php

namespace App\Services;

use App\Enums\DecisionCommission;
use App\Enums\StatutCommission;
use App\Enums\StatutEvaluation;
use App\Interfaces\CommissionAvancementInterface;
use App\Interfaces\CommissionPreparatoireInterface;
use App\Interfaces\EvaluationInterface;
use App\Models\CommissionAvancement;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Gère la commission d'avancement (CCN ARTF art. 69–70).
 *
 * Workflow :
 *   1. La commission préparatoire doit être clôturée.
 *   2. RH/DG crée la commission d'avancement (une par session).
 *   3. Pour chaque fiche finalisée, la commission :
 *      - Fixe la décision : `favorable` | `defavorable` | `reporte`.
 *      - Fixe le nombre d'échelons (0-2, même classe) si favorable.
 *      - Peut fixer une note d'avancement distincte (art. 70).
 *   4. Clôturer la commission → session peut être clôturée.
 *   5. RH applique l'avancement en paie : `avancerEchelon()` — idempotent.
 *
 * Seuil indicatif : note ≥ 12 → avancement accordé (à confirmer en commission).
 */
class CommissionAvancementService extends BaseService
{
    public function __construct(
        CommissionAvancementInterface             $repository,
        private readonly EvaluationInterface      $evaluationRepository,
        private readonly CommissionPreparatoireInterface $prepRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Ouvrir la commission d'avancement pour une session.
     * Prérequis : commission préparatoire clôturée.
     *
     * @throws ValidationException
     */
    public function ouvrir(int $sessionId, User $user, array $data = []): CommissionAvancement
    {
        // Prérequis : commission préparatoire clôturée
        $prep = $this->prepRepository->trouverParSession($sessionId);
        if (! $prep || ! $prep->statut->estClose()) {
            throw ValidationException::withMessages([
                'commission_preparatoire' => 'La commission préparatoire doit être clôturée avant d\'ouvrir la commission d\'avancement.',
            ]);
        }

        $existante = $this->repository->trouverParSession($sessionId);
        if ($existante) {
            throw ValidationException::withMessages([
                'session' => 'Une commission d\'avancement existe déjà pour cette session.',
            ]);
        }

        return $this->repository->create([
            'session_id'     => $sessionId,
            'statut'         => StatutCommission::EN_COURS->value,
            'date_ouverture' => $data['date_ouverture'] ?? now()->toDateString(),
            'created_by'     => $user->id,
            'observations'   => $data['observations'] ?? null,
        ]);
    }

    /**
     * Enregistrer la décision pour une fiche (art. 70).
     *
     * - `favorable`   → nombre_echelons ≥ 1 obligatoire (max 2, même classe).
     * - `defavorable` → nombre_echelons = 0.
     * - `reporte`     → différé, aucun avancement.
     *
     * @throws ValidationException
     */
    public function decider(
        int              $commissionId,
        int              $evaluationId,
        DecisionCommission $decision,
        int              $nombreEchelons = 0,
        ?float           $noteAvancement = null,
        ?string          $commentaire    = null,
    ): Evaluation {
        $this->assertEnCours($commissionId);
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        $this->assertFicheFinalisee($evaluation);

        // Validation métier
        if ($decision->donneDroitAvancement() && $nombreEchelons < 1) {
            throw ValidationException::withMessages([
                'nombre_echelons' => 'Une décision favorable nécessite au moins 1 échelon accordé.',
            ]);
        }

        if ($nombreEchelons > 2) {
            throw ValidationException::withMessages([
                'nombre_echelons' => 'Le nombre d\'échelons ne peut excéder 2 (même classe, art. 70).',
            ]);
        }

        if (! $decision->donneDroitAvancement()) {
            $nombreEchelons = 0;
        }

        return $this->evaluationRepository->update($evaluationId, [
            'commission_decision' => $decision->value,
            'nombre_echelons'     => $nombreEchelons,
            'note_avancement'     => $noteAvancement,
            'commentaire_rh'      => $commentaire ?? $evaluation->commentaire_rh,
        ]);
    }

    /**
     * Appliquer l'avancement d'échelon après décision favorable (lien paie, D6).
     * Idempotent : si `echelon_avance = true`, ne fait rien.
     *
     * @throws ValidationException
     */
    public function avancerEchelon(int $evaluationId): array
    {
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        if ($evaluation->echelon_avance) {
            return ['avance' => false, 'message' => 'Échelon déjà appliqué (idempotent).'];
        }

        if (! $evaluation->commission_decision || ! $evaluation->commission_decision->donneDroitAvancement()) {
            throw ValidationException::withMessages([
                'commission_decision' => 'L\'avancement n\'est possible que pour une décision favorable.',
            ]);
        }

        $agent = $evaluation->agent;
        if (! $agent) {
            throw ValidationException::withMessages(['agent' => 'Agent non trouvé.']);
        }

        $echelonActuelId = $agent->echelon_id;
        $nouvelEchelonId = null;

        // Chercher le prochain échelon (même classe)
        if ($echelonActuelId && $evaluation->nombre_echelons > 0) {
            $echelon = \App\Models\Echelon::find($echelonActuelId);
            if ($echelon) {
                // Incrémenter : trouver le Nième échelon suivant de la même classe
                $prochainEchelon = \App\Models\Echelon::query()
                    ->where('classe_id', $echelon->classe_id)
                    ->where('numero', $echelon->numero + $evaluation->nombre_echelons)
                    ->first();

                $nouvelEchelonId = $prochainEchelon?->id ?? $echelonActuelId; // plafonné si dernier
            }
        }

        // Mettre à jour l'agent
        if ($nouvelEchelonId && $nouvelEchelonId !== $echelonActuelId) {
            $agent->update(['echelon_id' => $nouvelEchelonId]);
        }

        // Marquer comme avancé (idempotent)
        $this->evaluationRepository->update($evaluationId, ['echelon_avance' => true]);

        return [
            'avance'              => true,
            'echelon_precedent_id' => $echelonActuelId,
            'echelon_nouveau_id'  => $nouvelEchelonId ?? $echelonActuelId,
            'message'             => 'Échelon appliqué avec succès.',
        ];
    }

    /**
     * Clôturer la commission d'avancement.
     * Après clôture, la session peut être fermée.
     *
     * @throws ValidationException
     */
    public function cloturer(int $commissionId, User $user, ?string $observations = null): CommissionAvancement
    {
        $commission = $this->assertEnCours($commissionId);

        return $this->repository->update($commissionId, [
            'statut'       => StatutCommission::CLOTUREE->value,
            'date_cloture' => now()->toDateString(),
            'cloture_par'  => $user->id,
            'observations' => $observations ?? $commission->observations,
        ]);
    }

    // ----------------------------------------------------------------
    private function assertEnCours(int $id): CommissionAvancement
    {
        $commission = $this->repository->findById($id);

        if ($commission->statut->estClose()) {
            throw ValidationException::withMessages([
                'statut' => 'La commission d\'avancement est déjà clôturée.',
            ]);
        }

        return $commission;
    }

    private function assertFicheFinalisee(Evaluation $evaluation): void
    {
        if ($evaluation->statut !== StatutEvaluation::FINALISEE) {
            throw ValidationException::withMessages([
                'evaluation' => 'Seules les fiches finalisées peuvent être traitées en commission.',
            ]);
        }
    }
}
