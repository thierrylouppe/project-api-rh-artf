<?php

namespace App\Enums;

enum StatutBonification: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVEE  = 'approuvee';
    case REJETEE    = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente',
            self::APPROUVEE  => 'Approuvée',
            self::REJETEE    => 'Rejetée',
        };
    }

    public function estTraitee(): bool
    {
        return $this !== self::EN_ATTENTE;
    }
}
