<?php

namespace App\Enums;

enum SourceDetailPaie: string
{
    case BASE = 'base';
    case AFFECTATION = 'affectation';
    case CALCUL_AUTO = 'calcul_auto';

    public function label(): string
    {
        return match ($this) {
            self::BASE => 'Salaire de base',
            self::AFFECTATION => 'Affectation',
            self::CALCUL_AUTO => 'Calcul automatique',
        };
    }
}
