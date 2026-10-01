<?php

namespace App\Enums;

enum TypeOrganismeSocial: string
{
    case CNSS = 'cnss';
    case MUTUELLE = 'mutuelle';
    case COMPLEMENTAIRE = 'complementaire';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::CNSS => 'CNSS',
            self::MUTUELLE => 'Mutuelle',
            self::COMPLEMENTAIRE => 'Complémentaire',
            self::AUTRE => 'Autre',
        };
    }
}
