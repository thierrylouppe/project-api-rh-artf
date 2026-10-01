<?php

namespace App\Enums;

enum MotifReconversion: string
{
    case BAISSE_ACTIVITE = 'baisse_activite';
    case REORGANISATION  = 'reorganisation';
    case MALADIE         = 'maladie';

    public function label(): string
    {
        return match ($this) {
            self::BAISSE_ACTIVITE => 'Baisse d\'activité',
            self::REORGANISATION  => 'Réorganisation interne',
            self::MALADIE         => 'Maladie constatée par médecin agréé',
        };
    }

    public function exigePiece(): bool
    {
        return $this === self::MALADIE;
    }
}
