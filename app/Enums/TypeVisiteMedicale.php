<?php

namespace App\Enums;

enum TypeVisiteMedicale: string
{
    case EMBAUCHE = 'embauche';
    case ANNUELLE = 'annuelle';
    case CONSULTATION = 'consultation';

    public function label(): string
    {
        return match ($this) {
            self::EMBAUCHE => 'Visite d\'embauche',
            self::ANNUELLE => 'Visite annuelle',
            self::CONSULTATION => 'Consultation',
        };
    }
}
