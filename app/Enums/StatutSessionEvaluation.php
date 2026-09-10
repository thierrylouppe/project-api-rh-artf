<?php

namespace App\Enums;

/**
 * Statut d'une session d'évaluation (cycle annuel).
 */
enum StatutSessionEvaluation: string
{
    case OUVERTE  = 'ouverte';
    case CLOTUREE = 'cloturee';
    case ANNULEE  = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::OUVERTE  => 'Ouverte',
            self::CLOTUREE => 'Clôturée',
            self::ANNULEE  => 'Annulée',
        };
    }

    /** Une session ouverte peut être clôturée ou annulée. */
    public function transitionsPossibles(): array
    {
        return match ($this) {
            self::OUVERTE  => [self::CLOTUREE, self::ANNULEE],
            self::CLOTUREE => [],
            self::ANNULEE  => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsPossibles(), true);
    }
}
