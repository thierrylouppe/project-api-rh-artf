<?php

namespace App\Enums;

enum StatutAffiliation: string
{
    case ACTIVE = 'active';
    case SUSPENDUE = 'suspendue';
    case CLOTUREE = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDUE => 'Suspendue',
            self::CLOTUREE => 'Clôturée',
        };
    }
}
