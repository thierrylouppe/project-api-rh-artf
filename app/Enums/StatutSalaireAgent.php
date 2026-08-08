<?php

namespace App\Enums;

enum StatutSalaireAgent: string
{
    case ACTIF   = 'actif';
    case CLOTURE = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF   => 'Actif',
            self::CLOTURE => 'Clôturé',
        };
    }
}
