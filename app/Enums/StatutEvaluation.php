<?php

namespace App\Enums;

/**
 * Statut d'une fiche d'évaluation individuelle.
 *
 * Workflow (Phase 1 implémente en_attente → signee_evaluateur) :
 *
 *   en_attente
 *     → en_cours         (première note saisie par le N+1)
 *     → notee            (toutes les questions actives ont une note, /20 calculé)
 *     → signee_evaluateur (N+1 signe la fiche)
 *     → signee_evalue     (agent signe) — Phase 2
 *     → en_reclamation    (agent dépose réclamation CCN art. 65) — Phase 2
 *     → en_validation_rh  (remontée RH)
 *     → finalisee         (RH conforme)
 *     → rejetee           (RH rejette, retour notateur)
 *     → annulee           (annulation RH)
 *
 * CCN ARTF art. 60–70.
 */
enum StatutEvaluation: string
{
    case EN_ATTENTE         = 'en_attente';
    case EN_COURS           = 'en_cours';
    case NOTEE              = 'notee';
    case SIGNEE_EVALUATEUR  = 'signee_evaluateur';
    case SIGNEE_EVALUE      = 'signee_evalue';
    case EN_RECLAMATION     = 'en_reclamation';
    case EN_VALIDATION_RH   = 'en_validation_rh';
    case FINALISEE          = 'finalisee';
    case REJETEE            = 'rejetee';
    case ANNULEE            = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE        => 'En attente de notation',
            self::EN_COURS          => 'En cours de notation',
            self::NOTEE             => 'Notée (non signée)',
            self::SIGNEE_EVALUATEUR => 'Signée par le notateur',
            self::SIGNEE_EVALUE     => 'Signée par l\'agent',
            self::EN_RECLAMATION    => 'En réclamation',
            self::EN_VALIDATION_RH  => 'En validation RH',
            self::FINALISEE         => 'Finalisée',
            self::REJETEE           => 'Rejetée',
            self::ANNULEE           => 'Annulée',
        };
    }

    /** Transitions autorisées depuis cet état. */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::EN_ATTENTE        => [self::EN_COURS, self::ANNULEE],
            self::EN_COURS          => [self::NOTEE, self::ANNULEE],
            self::NOTEE             => [self::SIGNEE_EVALUATEUR, self::EN_COURS, self::ANNULEE],
            self::SIGNEE_EVALUATEUR => [self::SIGNEE_EVALUE, self::NOTEE],
            self::SIGNEE_EVALUE     => [self::EN_RECLAMATION, self::EN_VALIDATION_RH],
            self::EN_RECLAMATION    => [self::EN_VALIDATION_RH],
            self::EN_VALIDATION_RH  => [self::FINALISEE, self::REJETEE, self::ANNULEE],
            self::REJETEE           => [self::EN_COURS],
            self::FINALISEE         => [],
            self::ANNULEE           => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsPossibles(), true);
    }

    /** Statuts indiquant que la fiche est terminée (lecture seule). */
    public function estTerminee(): bool
    {
        return in_array($this, [self::FINALISEE, self::ANNULEE], true);
    }

    /** La note a été portée à la connaissance de l'agent (art. 63–65) : PDF fiche autorisé. */
    public function peutTelechargerFichePdf(): bool
    {
        return in_array($this, [
            self::SIGNEE_EVALUE,
            self::EN_RECLAMATION,
            self::EN_VALIDATION_RH,
            self::FINALISEE,
            self::REJETEE,
        ], true);
    }
}
