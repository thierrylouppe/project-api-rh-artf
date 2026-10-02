<?php

namespace App\Enums;

enum OrigineDemandeCongeAnnuel: string
{
    case CAMPAGNE      = 'campagne';
    case APRES_CLOTURE = 'apres_cloture';

    public function label(): string
    {
        return match ($this) {
            self::CAMPAGNE      => 'Campagne',
            self::APRES_CLOTURE => 'Droit acquis après clôture',
        };
    }
}
