<?php

namespace App\Services;

use App\Interfaces\UserInterface;
use App\Models\Evaluation;
use App\Models\SessionEvaluation;
use App\Models\User;

/**
 * Gère les notifications liées au module Évaluation/Notation/Avancement (Phase 5.4).
 *
 * Domaine : `evaluation`
 * Actions déclenchées :
 *   - `session_ouverte`         → N+1 concernés (à noter)
 *   - `fiche_a_noter`           → N+1 de la fiche
 *   - `fiche_a_signer_evalue`   → Agent évalué
 *   - `fiche_en_validation_rh`  → Équipe RH
 *   - `fiche_finalisee`         → Agent (résultat)
 *   - `fiche_rejetee`           → N+1 (à corriger)
 *   - `commission_ouverte`      → DG + RH
 *   - `avancement_accorde`      → Agent (résultat)
 *   - `bonification_stage`      → RH (à traiter)
 *   - `avancement_exceptionnel` → RH (à traiter)
 */
class EvaluationNotificationService
{
    private const DOMAINE = 'evaluation';

    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly UserInterface $userRepository,
    ) {}

    // ----------------------------------------------------------------
    // Raccourcis « par agent » : les services métier manipulent des fiches
    // et des identifiants d'agent, pas des comptes utilisateurs. Un agent
    // sans compte ne reçoit simplement rien — ce n'est pas une erreur.
    // ----------------------------------------------------------------

    /** Compte utilisateur rattaché à un agent, s'il en a un. */
    private function compte(?int $agentId): ?User
    {
        return $agentId ? $this->userRepository->findByAgentId($agentId) : null;
    }

    /** Fiche attribuée : prévient le notateur. */
    public function ficheANoterPourSuperieur(Evaluation $fiche): void
    {
        $compte = $this->compte($fiche->superieur_id);
        if ($compte) {
            $this->ficheANoter($fiche, $compte);
        }
    }

    /** Notateur signataire : l'agent doit prendre connaissance et signer. */
    public function ficheASignerPourAgent(Evaluation $fiche): void
    {
        $compte = $this->compte($fiche->agent_id);
        if ($compte) {
            $this->ficheASignerEvalue($fiche, $compte);
        }
    }

    /** Fiche transmise : prévient l'équipe RH. */
    public function ficheEnValidationRhPourRh(Evaluation $fiche): void
    {
        $this->notificationService->notifierRole(
            'rh',
            self::DOMAINE,
            'fiche_en_validation_rh',
            "Une fiche d'évaluation est en attente de validation RH.",
            ['evaluation_id' => $fiche->id],
        );
    }

    /** Fiche finalisée : l'agent connaît son résultat. */
    public function ficheFinaliseePourAgent(Evaluation $fiche): void
    {
        $compte = $this->compte($fiche->agent_id);
        if ($compte) {
            $this->ficheFinalisee($fiche, $compte);
        }
    }

    /** Fiche rejetée : le notateur doit corriger. */
    public function ficheRejeteePourSuperieur(Evaluation $fiche): void
    {
        $compte = $this->compte($fiche->superieur_id);
        if ($compte) {
            $this->ficheRejetee($fiche, $compte);
        }
    }

    /** Commission ouverte : prévient la RH et la direction générale. */
    public function commissionOuvertePourResponsables(string $type, int $sessionId): void
    {
        foreach (['rh', 'directeur-general'] as $role) {
            $this->notificationService->notifierRole(
                $role,
                self::DOMAINE,
                "commission_{$type}_ouverte",
                "La commission {$type} de la session #{$sessionId} est ouverte.",
                ['session_id' => $sessionId, 'type_commission' => $type],
            );
        }
    }

    /** Échelon appliqué : l'agent est informé de son avancement. */
    public function avancementAccordePourAgent(Evaluation $fiche): void
    {
        $compte = $this->compte($fiche->agent_id);
        if ($compte) {
            $this->avancementAccorde($fiche, $compte);
        }
    }

    /** Demande de bonification de stage (art. 71) déposée : à traiter par la RH. */
    public function bonificationStagePourRh(int $agentId, int $bonificationId): void
    {
        $this->notificationService->notifierRole(
            'rh',
            self::DOMAINE,
            'bonification_stage',
            'Une demande de bonification stage (art. 71) est en attente de validation.',
            ['bonification_id' => $bonificationId, 'agent_id' => $agentId],
        );
    }

    /** Avancement exceptionnel (art. 72) proposé : à traiter par la RH. */
    public function avancementExceptionnelPourRh(int $agentId, int $avancementId): void
    {
        $this->notificationService->notifierRole(
            'rh',
            self::DOMAINE,
            'avancement_exceptionnel',
            'Un avancement exceptionnel (art. 72) est proposé pour traitement.',
            ['avancement_id' => $avancementId, 'agent_id' => $agentId],
        );
    }

    /** Session créée — notifier tous les N+1 concernés. */
    public function sessionOuverte(SessionEvaluation $session, iterable $superieurs): void
    {
        $this->notificationService->notifierEvenementGroupe(
            $superieurs,
            self::DOMAINE,
            'session_ouverte',
            "Une nouvelle session d'évaluation est ouverte. Vous avez des fiches à noter.",
            ['session_id' => $session->id],
        );
    }

    /** Fiche créée pour un N+1 (attribution auto). */
    public function ficheANoter(Evaluation $fiche, User $superieur): void
    {
        $this->notificationService->notifierEvenement(
            $superieur,
            self::DOMAINE,
            'fiche_a_noter',
            "Une nouvelle fiche d'évaluation vous est attribuée pour notation.",
            ['evaluation_id' => $fiche->id, 'agent_id' => $fiche->agent_id],
        );
    }

    /** N+1 a signé — l'agent doit signer à son tour. */
    public function ficheASignerEvalue(Evaluation $fiche, User $agent): void
    {
        $this->notificationService->notifierEvenement(
            $agent,
            self::DOMAINE,
            'fiche_a_signer_evalue',
            "Votre fiche d'évaluation est disponible. Veuillez en prendre connaissance et signer.",
            ['evaluation_id' => $fiche->id],
        );
    }

    /** Fiche transmise à la RH — notifier le(s) RH. */
    public function ficheEnValidationRh(Evaluation $fiche, iterable $rhUsers): void
    {
        $this->notificationService->notifierEvenementGroupe(
            $rhUsers,
            self::DOMAINE,
            'fiche_en_validation_rh',
            "Une fiche d'évaluation est en attente de validation RH.",
            ['evaluation_id' => $fiche->id, 'agent_id' => $fiche->agent_id],
        );
    }

    /** Fiche validée (finalisée) — notifier l'agent. */
    public function ficheFinalisee(Evaluation $fiche, User $agent): void
    {
        $this->notificationService->notifierEvenement(
            $agent,
            self::DOMAINE,
            'fiche_finalisee',
            "Votre évaluation a été validée par la RH. Résultat : {$fiche->mention} ({$fiche->note_globale}/20).",
            ['evaluation_id' => $fiche->id, 'note_globale' => $fiche->note_globale, 'mention' => $fiche->mention],
        );
    }

    /** Fiche rejetée par la RH — notifier le N+1 pour correction. */
    public function ficheRejetee(Evaluation $fiche, User $superieur): void
    {
        $this->notificationService->notifierEvenement(
            $superieur,
            self::DOMAINE,
            'fiche_rejetee',
            "Une fiche d'évaluation a été rejetée par la RH. Correction requise.",
            ['evaluation_id' => $fiche->id, 'agent_id' => $fiche->agent_id],
        );
    }

    /** Commission ouverte — notifier DG + RH. */
    public function commissionOuverte(string $type, int $sessionId, iterable $responsables): void
    {
        $this->notificationService->notifierEvenementGroupe(
            $responsables,
            self::DOMAINE,
            "commission_{$type}_ouverte",
            "La commission {$type} de la session #{$sessionId} est ouverte.",
            ['session_id' => $sessionId, 'type_commission' => $type],
        );
    }

    /** Avancement accordé après commission — notifier l'agent. */
    public function avancementAccorde(Evaluation $fiche, User $agent): void
    {
        $this->notificationService->notifierEvenement(
            $agent,
            self::DOMAINE,
            'avancement_accorde',
            "Votre avancement d'échelon a été accordé ({$fiche->nombre_echelons} échelon(s)).",
            ['evaluation_id' => $fiche->id, 'nombre_echelons' => $fiche->nombre_echelons],
        );
    }

    /** Demande de bonification stage soumise — notifier RH. */
    public function bonificationStage(int $agentId, int $bonificationId, iterable $rhUsers): void
    {
        $this->notificationService->notifierEvenementGroupe(
            $rhUsers,
            self::DOMAINE,
            'bonification_stage',
            "Une demande de bonification stage (art. 71) est en attente de validation.",
            ['bonification_id' => $bonificationId, 'agent_id' => $agentId],
        );
    }

    /** Avancement exceptionnel proposé (art. 72) — notifier RH. */
    public function avancementExceptionnel(int $agentId, int $avancementId, iterable $rhUsers): void
    {
        $this->notificationService->notifierEvenementGroupe(
            $rhUsers,
            self::DOMAINE,
            'avancement_exceptionnel',
            "Un avancement exceptionnel (art. 72) est proposé pour traitement.",
            ['avancement_id' => $avancementId, 'agent_id' => $agentId],
        );
    }
}
