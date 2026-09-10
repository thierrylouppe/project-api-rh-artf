<?php

namespace App\Services;

use App\Enums\StatutSessionEvaluation;
use App\Enums\StatutAgent;
use App\Interfaces\AffectationInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\EvaluationInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\SessionEvaluationInterface;
use App\Models\Agent;
use App\Models\SessionEvaluation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Gère le cycle de vie d'une session d'évaluation :
 *   – création (unicité de la session ouverte)
 *   – génération automatique des fiches éligibles
 *   – clôture / annulation
 *
 * CCN ARTF art. 60–62.
 */
class SessionEvaluationService extends BaseService
{
    public function __construct(
        SessionEvaluationInterface        $repository,
        private readonly AgentInterface        $agentRepository,
        private readonly AffectationInterface  $affectationRepository,
        private readonly NominationInterface   $nominationRepository,
        private readonly EvaluationInterface   $evaluationRepository,
    ) {
        parent::__construct($repository);
    }

    // ----------------------------------------------------------------
    // CRUD avec garde d'unicité
    // ----------------------------------------------------------------

    /** @throws ValidationException */
    public function create(array $data): SessionEvaluation
    {
        $this->assertPasDeSessionOuverte();

        $session = $this->repository->create(array_merge($data, ['statut' => 'ouverte']));

        // Génère immédiatement les fiches pour tous les agents éligibles
        $this->genererFiches($session);

        return $session->fresh(['evaluations']);
    }

    /** Clôturer la session. */
    public function cloturer(int $id, int $userId): SessionEvaluation
    {
        /** @var SessionEvaluation $session */
        $session = $this->repository->findById($id);

        if (! $session->statut->peutTransitionnerVers(StatutSessionEvaluation::CLOTUREE)) {
            throw ValidationException::withMessages([
                'statut' => "La session est déjà {$session->statut->label()} et ne peut pas être clôturée.",
            ]);
        }

        return $this->repository->update($id, [
            'statut'       => StatutSessionEvaluation::CLOTUREE->value,
            'cloturee_par' => $userId,
            'cloturee_at'  => now(),
            'fin_session'  => $session->fin_session ?? now()->toDateString(),
        ]);
    }

    /** Annuler la session (et ses fiches). */
    public function annuler(int $id, int $userId): SessionEvaluation
    {
        /** @var SessionEvaluation $session */
        $session = $this->repository->findById($id);

        if (! $session->statut->peutTransitionnerVers(StatutSessionEvaluation::ANNULEE)) {
            throw ValidationException::withMessages([
                'statut' => "La session est déjà {$session->statut->label()} et ne peut pas être annulée.",
            ]);
        }

        // Annule toutes les fiches en attente ou en cours
        $this->evaluationRepository->getBySession($id)
            ->filter(fn ($e) => in_array($e->statut->value, ['en_attente', 'en_cours'], true))
            ->each(fn ($e) => $this->evaluationRepository->update($e->id, ['statut' => 'annulee']));

        return $this->repository->update($id, [
            'statut'       => StatutSessionEvaluation::ANNULEE->value,
            'cloturee_par' => $userId,
            'cloturee_at'  => now(),
        ]);
    }

    // ----------------------------------------------------------------
    // Génération automatique des fiches
    // ----------------------------------------------------------------

    /**
     * Crée une fiche `en_attente` pour chaque agent éligible
     * qui n'en possède pas encore dans cette session.
     */
    public function genererFiches(SessionEvaluation $session): Collection
    {
        $agentsEligibles = $this->agentsEligibles($session);
        $creees = collect();

        foreach ($agentsEligibles as $agent) {
            $existing = $this->evaluationRepository->trouverParAgentSession($agent->id, $session->id);
            if ($existing) {
                continue;
            }

            $superieurId = $this->resoudreSuperieurId((int) $agent->id);

            // CCN §8 : si aucun N+1 identifiable, on liste l'agent « sans supérieur »
            // mais on NE crée PAS de fiche (pas de notateur = pas d'évaluation possible)
            if ($superieurId === null) {
                continue;
            }

            $evaluation = $this->evaluationRepository->create([
                'session_id'   => $session->id,
                'agent_id'     => $agent->id,
                'superieur_id' => $superieurId,
                'statut'       => 'en_attente',
            ]);

            $creees->push($evaluation);
        }

        return $creees;
    }

    /**
     * Retourne les agents éligibles pour la session mais sans N+1 identifiable.
     * Vue RH : permet de corriger les affectations manquantes avant de perdre des évaluations.
     */
    public function agentsSansSuperieur(SessionEvaluation $session): Collection
    {
        // IDs des agents qui ont déjà une fiche dans cette session
        $agentsAvecFiche = $this->evaluationRepository->getBySession($session->id)
            ->pluck('agent_id');

        return $this->agentsEligibles($session)
            ->filter(function (Agent $agent) use ($agentsAvecFiche) {
                // Éligible, mais pas de fiche = pas de N+1 trouvé
                if ($agentsAvecFiche->contains($agent->id)) {
                    return false;
                }
                // Double vérification : vraiment pas de supérieur ?
                return $this->resoudreSuperieurId($agent->id) === null;
            })
            ->values();
    }

    // ----------------------------------------------------------------
    // Éligibilité (CCN art. 60–62, D9)
    // ----------------------------------------------------------------

    /**
     * Retourne les agents pouvant être évalués pour cette session.
     *
     * Règles (doc/REFERENTIELS-EVALUATION-NOTATION.md §5) :
     *   1. Pas Directeur Général (D9)
     *   2. `date_prise_service` connue (obligatoire pour calculer parité + ancienneté)
     *   3. Cycle 24 mois (D2) : écart en années ≥ 2 depuis la **date de dernière notation finalisée**
     *      (ou `date_prise_service` au 1er cycle). Calcul sur années calendaires.
     *   4. Parité d'année       : année de prise de service paire ↔ session.type_annee = 'paire'
     *                             (filtre ignoré si session.type_annee est null)
     *   5. Semestre             : mois 1–6 → semestre 1 ; mois 7–12 → semestre 2
     *                             (filtre ignoré si session.semestre est null)
     *   6. Statut actif, non exempté (stagiaire / détachement / position_exceptionnelle…)
     */
    public function agentsEligibles(SessionEvaluation $session): Collection
    {
        $statutsExclus = [
            StatutAgent::STAGIAIRE->value,
            StatutAgent::DETACHEMENT->value,
            StatutAgent::POSITION_EXCEPTIONNELLE->value,
            StatutAgent::INACTIF->value,
            StatutAgent::RETRAITE->value,
            StatutAgent::SUSPENDU->value,
            StatutAgent::ARCHIVE->value,
        ];

        $anneeSession = (int) $session->debut_session->year;

        // D9 : IDs des agents qui ont une nomination active DG
        $dgIds = $this->nominationRepository->getAll(['statut' => 'active'])
            ->filter(fn ($n) => $n->poste === 'Directeur Général')
            ->pluck('agent_id')
            ->unique();

        $agents = $this->agentRepository->getAll()
            ->filter(function (Agent $agent) use ($statutsExclus, $anneeSession, $session, $dgIds) {

                // Règle 6 : statut non exempté
                if (in_array($agent->statut, $statutsExclus, true)) {
                    return false;
                }

                // Règle 1 : pas DG
                if ($dgIds->contains($agent->id)) {
                    return false;
                }

                // Règle 2 : date de prise de service connue
                if (! $agent->date_prise_service) {
                    return false;
                }

                $anneeEmbauche    = (int) $agent->date_prise_service->year;
                $moisEmbauche     = (int) $agent->date_prise_service->month;

                // Règle 3 : cycle 24 mois (D2)
                // Référence = date de dernière notation finalisée, ou date_prise_service si 1er cycle
                $dateRef = $this->evaluationRepository->dateDerniereEvaluationFinalisee($agent->id)
                    ?? $agent->date_prise_service;

                $anneeRef = (int) $dateRef->year;

                if (($anneeSession - $anneeRef) < 2) {
                    return false;
                }

                // Règle 4 : parité d'année (seulement si session.type_annee est défini)
                if ($session->type_annee !== null) {
                    $paritéAgent   = ($anneeEmbauche % 2 === 0) ? 'paire' : 'impaire';
                    if ($paritéAgent !== $session->type_annee) {
                        return false;
                    }
                }

                // Règle 5 : semestre (seulement si session.semestre est défini)
                if ($session->semestre !== null) {
                    $semestreAgent = ($moisEmbauche <= 6) ? 1 : 2;
                    if ($semestreAgent !== (int) $session->semestre) {
                        return false;
                    }
                }

                return true;
            });

        return $agents->values();
    }

    /** Résout le superieur_id (agent_id du N+1) depuis l'affectation active. */
    private function resoudreSuperieurId(int $agentId): ?int
    {
        $affectation = $this->affectationRepository->getActive($agentId);

        return $affectation?->superieur_hierarchique_id;
    }

    // ----------------------------------------------------------------
    // Helpers privés
    // ----------------------------------------------------------------

    /** @throws ValidationException */
    private function assertPasDeSessionOuverte(): void
    {
        /** @var \App\Repositories\SessionEvaluationRepository $repo */
        $repo = $this->repository;

        if ($repo->trouverOuverte()) {
            throw ValidationException::withMessages([
                'session' => 'Une session d\'évaluation est déjà ouverte. Clôturez-la avant d\'en créer une nouvelle.',
            ]);
        }
    }
}
