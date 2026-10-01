<?php

namespace App\Enums;

enum QualiteAgeAyantDroit: string
{
    case STANDARD = 'standard';
    case APPRENTISSAGE = 'apprentissage';
    case ETUDES = 'etudes';
    case INFIRMITE = 'infirmite';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard (moins de 16 ans)',
            self::APPRENTISSAGE => 'Apprentissage (moins de 17 ans)',
            self::ETUDES => 'Études secondaires ou supérieures (moins de 21 ans)',
            self::INFIRMITE => 'Infirmité ou maladie incurable (moins de 21 ans)',
        };
    }

    /** Limite d'âge CCN art. 59 (« moins de »). */
    public function ageLimite(): int
    {
        return match ($this) {
            self::STANDARD => 16,
            self::APPRENTISSAGE => 17,
            self::ETUDES, self::INFIRMITE => 21,
        };
    }
}
