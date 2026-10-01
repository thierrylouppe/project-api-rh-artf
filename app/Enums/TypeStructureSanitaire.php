<?php

namespace App\Enums;

enum TypeStructureSanitaire: string
{
    case MEDECIN = 'medecin';
    case FORMATION_SANITAIRE = 'formation_sanitaire';
    case OPTICIEN = 'opticien';
    case PHARMACIE = 'pharmacie';

    public function label(): string
    {
        return match ($this) {
            self::MEDECIN => 'Médecin agréé',
            self::FORMATION_SANITAIRE => 'Formation sanitaire',
            self::OPTICIEN => 'Opticien agréé',
            self::PHARMACIE => 'Pharmacie',
        };
    }
}
