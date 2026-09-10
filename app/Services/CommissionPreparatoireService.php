<?php

namespace App\Services;

use App\Enums\StatutCommission;
use App\Enums\StatutEvaluation;
use App\Interfaces\CommissionPreparatoireInterface;
use App\Interfaces\EvaluationInterface;
use App\Models\CommissionPreparatoire;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Gère la commission préparatoire (CCN ARTF art. 68).
 *
 * Workflow :
 *   1. RH/DG crée la commission (une par session).
 *   2. Pour chaque fiche finalisée, la commission peut :
 *      - Harmoniser la note : `noterFiche()` → met à jour `commission_note`.
 *      - Rédiger la note de synthèse (art. 67) : `rediger()` → met à jour `note_synthese`.
 *      - Une alerte logicielle est générée si |commission_note − note_globale| > 5 (non bloquant).
 *   3. Clôturer la commission → prérequis pour ouvrir la commission d'avancement.
 *
 * Présidée par le DG. Contrôle la cohérence des appréciations et motivations des notateurs.
 */
class CommissionPreparatoireService extends BaseService
{
    public function __construct(
        CommissionPreparatoireInterface      $repository,
        private readonly EvaluationInterface $evaluationRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Créer la commission préparatoire pour une session.
     * Une seule commission par session (unicité DB).
     *
     * @throws ValidationException
     */
    public function ouvrir(int $sessionId, User $user, array $data = []): CommissionPreparatoire
    {
        $existante = $this->repository->trouverParSession($sessionId);
        if ($existante) {
            throw ValidationException::withMessages([
                'session' => 'Une commission préparatoire existe déjà pour cette session.',
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
     * Harmoniser la note d'une fiche (commission_note).
     * Alerte logicielle (non bloquante) si |commission_note − note_globale| > 5.
     *
     * @throws ValidationException
     */
    public function noterFiche(int $commissionId, int $evaluationId, float $commissionNote): array
    {
        $commission = $this->assertEnCours($commissionId);
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        $this->assertFicheFinalisee($evaluation);

        $ecart    = abs($commissionNote - ($evaluation->note_globale ?? 0));
        $alerteEcart = $ecart > 5;

        $this->evaluationRepository->update($evaluationId, [
            'commission_note' => $commissionNote,
        ]);

        return [
            'alerte_ecart' => $alerteEcart,
            'ecart'        => round($ecart, 2),
            'message'      => $alerteEcart
                ? "⚠️ Écart de {$ecart} pts entre la note N+1 ({$evaluation->note_globale}) et la note commission ({$commissionNote})."
                : 'Note commission enregistrée.',
        ];
    }

    /**
     * Rédiger / mettre à jour la note de synthèse (art. 67).
     *
     * @throws ValidationException
     */
    public function redigerSynthese(int $commissionId, int $evaluationId, string $noteSynthese): Evaluation
    {
        $this->assertEnCours($commissionId);
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        $this->assertFicheFinalisee($evaluation);

        return $this->evaluationRepository->update($evaluationId, [
            'note_synthese' => $noteSynthese,
        ]);
    }

    /**
     * Clôturer la commission préparatoire.
     * Prérequis pour ouvrir la commission d'avancement.
     *
     * @throws ValidationException
     */
    public function cloturer(int $commissionId, User $user, ?string $observations = null): CommissionPreparatoire
    {
        $commission = $this->assertEnCours($commissionId);

        return $this->repository->update($commissionId, [
            'statut'       => StatutCommission::CLOTUREE->value,
            'date_cloture' => now()->toDateString(),
            'cloture_par'  => $user->id,
            'observations' => $observations ?? $commission->observations,
        ]);
    }

    /** Résumé des fiches avec alerte d'écart (|commission_note - note_globale| > 5). */
    public function fichesAvecAlerte(int $sessionId): Collection
    {
        return \App\Models\Evaluation::query()
            ->where('session_id', $sessionId)
            ->where('statut', StatutEvaluation::FINALISEE->value)
            ->whereNotNull('commission_note')
            ->whereRaw('ABS(commission_note - note_globale) > 5')
            ->with(['agent'])
            ->get();
    }

    // ----------------------------------------------------------------
    private function assertEnCours(int $id): CommissionPreparatoire
    {
        $commission = $this->repository->findById($id);

        if ($commission->statut->estClose()) {
            throw ValidationException::withMessages([
                'statut' => 'La commission préparatoire est déjà clôturée.',
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
