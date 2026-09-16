<?php

namespace App\Enums;

enum TypeChangementSalaireAgent: string
{
    case INITIAL             = 'initial';
    case AVANCEMENT_ECHELON  = 'avancement_echelon';
    case CORRECTION          = 'correction';
    case REVALORISATION      = 'revalorisation';
    case RECLASSEMENT        = 'reclassement';
    case HORS_CLASSE         = 'hors_classe';
    case RECONVERSION        = 'reconversion';
    case CONFIRMATION_ESSAI  = 'confirmation_essai';

    public function label(): string
    {
        return match ($this) {
            self::INITIAL            => 'Salaire initial',
            self::AVANCEMENT_ECHELON => 'Avancement d\'échelon',
            self::CORRECTION         => 'Correction',
            self::REVALORISATION     => 'Revalorisation',
            self::RECLASSEMENT       => 'Reclassement de classe',
            self::HORS_CLASSE        => 'Hors classe',
            self::RECONVERSION       => 'Reconversion',
            self::CONFIRMATION_ESSAI => 'Confirmation de période d\'essai',
        };
    }
}
