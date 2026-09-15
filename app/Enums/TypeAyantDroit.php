<?php

namespace App\Enums;

enum TypeAyantDroit: string
{
    case CONJOINT = 'conjoint';
    case ENFANT = 'enfant';

    public function label(): string
    {
        return match ($this) {
            self::CONJOINT => 'Conjoint',
            self::ENFANT => 'Enfant',
        };
    }
}
