<?php

namespace App\Enums;

/**
 * Statut d'une commission (préparatoire ou d'avancement).
 */
enum StatutCommission: string
{
    case EN_COURS = 'en_cours';
    case CLOTUREE = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::EN_COURS => 'En cours',
            self::CLOTUREE => 'Clôturée',
        };
    }

    public function estClose(): bool
    {
        return $this === self::CLOTUREE;
    }
}
