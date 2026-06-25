<?php

namespace App\Enums;

enum StatutConventionStage: string
{
    case EN_COURS = 'EN_COURS';
    case TERMINE  = 'TERMINE';
    case ROMPU    = 'ROMPU';

    public function label(): string
    {
        return match ($this) {
            self::EN_COURS => 'En cours',
            self::TERMINE  => 'Terminé',
            self::ROMPU    => 'Rompu',
        };
    }

    public function estActif(): bool
    {
        return $this === self::EN_COURS;
    }
}
