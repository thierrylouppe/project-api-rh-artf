<?php

namespace App\Enums;

enum TypeActionFormation: string
{
    case SUR_LE_TAS = 'sur_le_tas';
    case SEMINAIRE = 'seminaire';
    case PERFECTIONNEMENT = 'perfectionnement';
    case QUALIFICATION = 'qualification';
    case ECOLE = 'ecole';
    case CAMRTF = 'camrtf';

    public function label(): string
    {
        return match ($this) {
            self::SUR_LE_TAS => 'Formation sur le tas',
            self::SEMINAIRE => 'Séminaire',
            self::PERFECTIONNEMENT => 'Perfectionnement / recyclage',
            self::QUALIFICATION => 'Qualification / spécialisation',
            self::ECOLE => 'École ou institut spécialisé',
            self::CAMRTF => 'CAMRTF',
        };
    }

    /** Durée maximale en mois (CCN art. 99 et 102). Null = pas de plafond conventionnel. */
    public function dureeMaxMois(): ?int
    {
        return match ($this) {
            self::PERFECTIONNEMENT => 9,
            self::QUALIFICATION, self::ECOLE => 36,
            default => null,
        };
    }
}
