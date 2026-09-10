<?php

namespace App\Enums;

/**
 * Statut d'une réclamation déposée par un agent (CCN ARTF art. 65).
 */
enum StatutReclamation: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTEE   = 'acceptee';
    case REJETEE    = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de traitement',
            self::ACCEPTEE   => 'Acceptée (renvoi au notateur)',
            self::REJETEE    => 'Rejetée (maintien de la note)',
        };
    }

    public function estTraitee(): bool
    {
        return $this !== self::EN_ATTENTE;
    }
}
