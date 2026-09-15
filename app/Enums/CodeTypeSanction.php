<?php

namespace App\Enums;

enum CodeTypeSanction: string
{
    case AVERTISSEMENT_ECRIT = 'avertissement_ecrit';
    case BLAME_ECRIT = 'blame_ecrit';
    case MISE_A_PIED = 'mise_a_pied';
    case LICENCIEMENT = 'licenciement';

    public function label(): string
    {
        return match ($this) {
            self::AVERTISSEMENT_ECRIT => 'Avertissement écrit',
            self::BLAME_ECRIT => 'Blâme écrit',
            self::MISE_A_PIED => 'Mise à pied sans rémunération',
            self::LICENCIEMENT => 'Licenciement',
        };
    }

    public function exigeNbJours(): bool
    {
        return $this === self::MISE_A_PIED;
    }
}
