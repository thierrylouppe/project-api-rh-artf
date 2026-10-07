<?php

namespace App\Enums;

enum StatutCampagneCongeAnnuel: string
{
    case BROUILLON = 'brouillon';
    case OUVERTE   = 'ouverte';
    case CLOTUREE  = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::OUVERTE   => 'Ouverte',
            self::CLOTUREE  => 'Clôturée',
        };
    }
}
