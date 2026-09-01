<?php

namespace App\Enums;

enum StatutAbsence: string
{
    case EN_ATTENTE = 'en_attente';
    case VALIDEE    = 'validee';
    case REJETEE    = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE    => 'Validée',
            self::REJETEE    => 'Rejetée',
        };
    }
}
