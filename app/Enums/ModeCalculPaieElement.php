<?php

namespace App\Enums;

enum ModeCalculPaieElement: string
{
    case MONTANT_FIXE = 'montant_fixe';
    case POURCENTAGE_BASE = 'pourcentage_base';
    case FORMULE_CCN = 'formule_ccn';
    case BAREME_CCN = 'bareme_ccn';

    public function label(): string
    {
        return match ($this) {
            self::MONTANT_FIXE => 'Montant fixe',
            self::POURCENTAGE_BASE => 'Pourcentage du salaire de base',
            self::FORMULE_CCN => 'Formule CCN',
            self::BAREME_CCN => 'Barème CCN',
        };
    }
}
