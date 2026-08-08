<?php

namespace App\Enums;

enum TypeChangementSalaireAgent: string
{
    case INITIAL             = 'initial';
    case AVANCEMENT_ECHELON  = 'avancement_echelon';
    case CORRECTION          = 'correction';
    case REVALORISATION      = 'revalorisation';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL            => 'Salaire initial',
            self::AVANCEMENT_ECHELON => 'Avancement d\'échelon',
            self::CORRECTION         => 'Correction',
            self::REVALORISATION     => 'Revalorisation',
        };
    }
}
