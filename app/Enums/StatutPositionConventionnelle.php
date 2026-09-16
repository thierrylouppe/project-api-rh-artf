<?php

namespace App\Enums;

enum StatutPositionConventionnelle: string
{
    case SOUMISE  = 'soumise';
    case ACTIVE   = 'active';
    case CLOTUREE = 'cloturee';
    case REJETEE  = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::SOUMISE  => 'Soumise',
            self::ACTIVE   => 'Active',
            self::CLOTUREE => 'Clôturée',
            self::REJETEE  => 'Rejetée',
        };
    }
}
