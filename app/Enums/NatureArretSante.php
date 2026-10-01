<?php

namespace App\Enums;

enum NatureArretSante: string
{
    case MALADIE = 'maladie';
    case ACCIDENT_TRAVAIL = 'accident_travail';
    case MALADIE_PROFESSIONNELLE = 'maladie_professionnelle';
    case ACCIDENT_NON_PROFESSIONNEL = 'accident_non_professionnel';

    public function label(): string
    {
        return match ($this) {
            self::MALADIE => 'Maladie',
            self::ACCIDENT_TRAVAIL => 'Accident du travail',
            self::MALADIE_PROFESSIONNELLE => 'Maladie professionnelle',
            self::ACCIDENT_NON_PROFESSIONNEL => 'Accident non professionnel',
        };
    }

    public function articleCcn(): string
    {
        return $this === self::ACCIDENT_NON_PROFESSIONNEL ? '135' : '132';
    }

    public function estAtMp(): bool
    {
        return in_array($this, [self::ACCIDENT_TRAVAIL, self::MALADIE_PROFESSIONNELLE], true);
    }

    public function codePaie(): CodePaieElement
    {
        return $this === self::ACCIDENT_NON_PROFESSIONNEL
            ? CodePaieElement::ALLOCATION_ACCIDENT_NON_PRO
            : CodePaieElement::ALLOCATION_MALADIE;
    }

    /** Mois d'allocation art. 132 (hors majoration 133). Null si &lt; 1 an. */
    public function nbMoisBareme(int $annees): ?int
    {
        if ($this === self::ACCIDENT_NON_PROFESSIONNEL) {
            return 12;
        }

        return match (true) {
            $annees < 1 => null,
            $annees <= 5 => 6,
            $annees <= 10 => 7,
            $annees <= 15 => 8,
            $annees <= 20 => 9,
            default => 10,
        };
    }

    /** Majoration art. 133 si au moins 2 enfants à charge. */
    public function nbMoisMajoration(int $annees, int $nbEnfants): int
    {
        if ($this === self::ACCIDENT_NON_PROFESSIONNEL || $nbEnfants < 2) {
            return 0;
        }

        return match (true) {
            $annees < 1 => 0,
            $annees <= 5 => 3,
            $annees <= 15 => 4,
            default => 5,
        };
    }
}
