<?php

namespace App\Enums;

enum StatutReclassement: string
{
    case SOUMIS    = 'soumis';
    case APPROUVE  = 'approuve';
    case REJETE    = 'rejete';
    case APPLIQUE  = 'applique';
    case ANNULE    = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::SOUMIS   => 'Soumis',
            self::APPROUVE => 'Approuvé',
            self::REJETE   => 'Rejeté',
            self::APPLIQUE => 'Appliqué',
            self::ANNULE   => 'Annulé',
        };
    }

    public function estOuvert(): bool
    {
        return in_array($this, [self::SOUMIS, self::APPROUVE], true);
    }
}
