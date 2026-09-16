<?php

namespace App\Enums;

enum PeriodicitePaieElement: string
{
    case MENSUEL = 'mensuel';
    case SEMESTRIEL = 'semestriel';
    case ANNUEL = 'annuel';
    case PONCTUEL = 'ponctuel';
    case JOURNALIER = 'journalier';

    public function label(): string
    {
        return match ($this) {
            self::MENSUEL => 'Mensuel',
            self::SEMESTRIEL => 'Semestriel',
            self::ANNUEL => 'Annuel',
            self::PONCTUEL => 'Ponctuel',
            self::JOURNALIER => 'Journalier',
        };
    }
}
