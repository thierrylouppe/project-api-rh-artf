<?php

namespace App\Services;

use App\Enums\StatutCommission;
use App\Enums\StatutSessionEvaluation;
use App\Enums\StatutAgent;
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
        SessionEvaluationInterface                 $repository,
        private readonly AgentInterface            $agentRepository,
        private readonly NominationInterface       $nominationRepository,
        private readonly EvaluationInterface       $evaluationRepository,
        private readonly SuperieurHierarchiqueService $superieurService,
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

    /** Clôturer la session.
     *
     * Phase 4 : prérequis = toutes les fiches finalisées + deux commissions clôturées.
     *
     * @throws ValidationException
     */
    public function cloturer(int $id, int $userId): SessionEvaluation
    {
        /** @var SessionEvaluation $session */
        $session = $this->repository->findById($id);

        if (! $session->statut->peutTransitionnerVers(StatutSessionEvaluation::CLOTUREE)) {
            throw ValidationException::withMessages([
                'statut' => "La session est déjà {$session->statut->label()} et ne peut pas être clôturée.",
            ]);
        }

        // Phase 4 — Vérifier que les deux commissions sont clôturées
        // (uniquement si la session contient des fiches finalisées)
        $fichesFinaliseesExistent = \App\Models\Evaluation::where('session_id', $id)
            ->where('statut', \App\Enums\StatutEvaluation::FINALISEE->value)
            ->exists();

        if ($fichesFinaliseesExistent) {
            $prep = \App\Models\CommissionPreparatoire::where('session_id', $id)->first();
            $avan = \App\Models\CommissionAvancement::where('session_id', $id)->first();

            if (! $prep || $prep->statut !== StatutCommission::CLOTUREE) {
                throw ValidationException::withMessages([
                    'commission_preparatoire' => 'La commission préparatoire doit être clôturée avant de clôturer la session.',
                ]);
            }

            if (! $avan || $avan->statut !== StatutCommission::CLOTUREE) {
                throw ValidationException::withMessages([
                    'commission_avancement' => 'La commission d\'avancement doit être clôturée avant de clôturer la session.',
                ]);
            }
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

            $notateur = $this->resoudreNotateur($session, (int) $agent->id);

            // CCN art. 62 : si aucun N+1 identifiable, on liste l'agent « sans supérieur »
            // mais on NE crée PAS de fiche (pas de notateur = pas d'évaluation possible)
            if ($notateur === null) {
                continue;
            }

            $evaluation = $this->evaluationRepository->create([
                'session_id'              => $session->id,
                'agent_id'                => $agent->id,
                'superieur_id'            => $notateur['superieur_id'],
                'affectation_notation_id' => $notateur['affectation']->id,
                'statut'                  => 'en_attente',
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
            ->filter(function (Agent $agent) use ($agentsAvecFiche, $session) {
                // Éligible, mais pas de fiche = pas de N+1 trouvé
                if ($agentsAvecFiche->contains($agent->id)) {
                    return false;
                }
                // Double vérification : vraiment pas de supérieur ?
                return $this->resoudreNotateur($session, (int) $agent->id) === null;
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
        // Construire la liste depuis l'enum — inclut automatiquement
        // les nouvelles positions CCN (disponibilite, sous_le_drapeau…)
        $statutsExclus = array_merge(
            // Positions exemptées de notation (exempteDeNotation = true)
            array_map(
                fn (StatutAgent $s) => $s->value,
                array_filter(StatutAgent::cases(), fn (StatutAgent $s) => $s->exempteDeNotation())
            ),
            // Statuts "hors activité" non couverts par exempteDeNotation
            [
                StatutAgent::INACTIF->value,
                StatutAgent::RETRAITE->value,
                StatutAgent::SUSPENDU->value,
                StatutAgent::ARCHIVE->value,
            ]
        );

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

    /**
     * Statistiques d'une session (Phase 5 — lot 5.6).
     *
     * Retourne :
     *  - `total` : total des fiches
     *  - `par_statut` : compteur par valeur de StatutEvaluation
     *  - `moyenne` : note globale moyenne (fiches notées uniquement)
     *  - `mentions` : répartition par mention (fiches avec note_globale)
     */
    public function stats(int $sessionId): array
    {
        $fiches = \App\Models\Evaluation::query()
            ->where('session_id', $sessionId)
            ->select(['statut', 'note_globale', 'mention'])
            ->get();

        // Compteurs par statut
        $parStatut = $fiches->groupBy('statut')
            ->map(fn ($groupe) => $groupe->count())
            ->toArray();

        // Moyenne note globale (seulement fiches avec note)
        $avecNote  = $fiches->whereNotNull('note_globale');
        $moyenne   = $avecNote->count() > 0
            ? round($avecNote->avg('note_globale'), 2)
            : null;

        // Répartition mentions
        $mentions  = $fiches->whereNotNull('mention')
            ->groupBy('mention')
            ->map(fn ($g) => $g->count())
            ->toArray();

        return [
            'session_id' => $sessionId,
            'total'      => $fiches->count(),
            'par_statut' => $parStatut,
            'moyenne'    => $moyenne,
            'mentions'   => $mentions,
        ];
    }

    /**
     * CCN art. 62 : notateur = poste où l'agent a servi le plus longtemps
     * sur les 24 mois précédant l'ouverture de session.
     *
     * @return array{superieur_id: int, affectation: \App\Models\Affectation, duree_jours: int}|null
     */
    private function resoudreNotateur(SessionEvaluation $session, int $agentId): ?array
    {
        [$debut, $fin] = $this->superieurService->periodeNotation($session);

        $priseService = $this->agentRepository->findById($agentId)?->date_prise_service;
        if ($priseService && Carbon::parse($priseService)->greaterThan($debut)) {
            $debut = Carbon::parse($priseService)->startOfDay();
        }

        return $this->superieurService->resoudreNotateurPourPeriode($agentId, $debut, $fin);
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
