<?php

namespace App\Enums;

enum TypePrestation: string
{
    case CAPITAL_DECES = 'capital_deces';
    case PRIME_ENFANTS_DECES = 'prime_enfants_deces';
    case FRAIS_FUNERAIRES = 'frais_funeraires';
    case ALLOCATION_DECES_RETRAITE = 'allocation_deces_retraite';
    case INDEMNITE_RETRAITE = 'indemnite_retraite';

    public const PLAFOND_FUNERAIRES = 2_000_000;

    public const PRIME_ENFANT_FCFA = 100_000;

    public const ALLOCATION_DECES_RETRAITE_FCFA = 500_000;

    public function label(): string
    {
        return match ($this) {
            self::CAPITAL_DECES => 'Capital décès',
            self::PRIME_ENFANTS_DECES => 'Prime enfants à charge (décès)',
            self::FRAIS_FUNERAIRES => 'Frais funéraires',
            self::ALLOCATION_DECES_RETRAITE => 'Allocation décès salarié retraité',
            self::INDEMNITE_RETRAITE => 'Indemnité d\'admission à la retraite',
        };
    }

    public function articleCcn(): string
    {
        return match ($this) {
            self::ALLOCATION_DECES_RETRAITE => '120',
            self::INDEMNITE_RETRAITE => '119',
            default => '121',
        };
    }

    public function codePaie(): CodePaieElement
    {
        return match ($this) {
            self::CAPITAL_DECES => CodePaieElement::CAPITAL_DECES,
            self::PRIME_ENFANTS_DECES => CodePaieElement::PRIME_ENFANTS_DECES,
            self::FRAIS_FUNERAIRES => CodePaieElement::FRAIS_FUNERAIRES,
            self::ALLOCATION_DECES_RETRAITE => CodePaieElement::ALLOCATION_DECES_RETRAITE,
            self::INDEMNITE_RETRAITE => CodePaieElement::INDEMNITE_RETRAITE,
        };
    }

    public function estDeces(): bool
    {
        return $this !== self::INDEMNITE_RETRAITE;
    }

    public function exigeMontantDemande(): bool
    {
        return $this === self::FRAIS_FUNERAIRES;
    }

    public function bloqueSiEssai(): bool
    {
        return in_array($this, [self::CAPITAL_DECES, self::INDEMNITE_RETRAITE], true);
    }

    public function reserveRetraite(): bool
    {
        return $this === self::ALLOCATION_DECES_RETRAITE;
    }

    public function exigeSalaire(): bool
    {
        return in_array($this, [self::CAPITAL_DECES, self::INDEMNITE_RETRAITE], true);
    }

    public function nbMoisBareme(int $anneesRevolues): ?int
    {
        return match ($this) {
            self::CAPITAL_DECES => match (true) {
                $anneesRevolues < 1 => 5,
                $anneesRevolues <= 5 => 9,
                $anneesRevolues <= 15 => 12,
                $anneesRevolues <= 20 => 15,
                default => 18,
            },
            self::INDEMNITE_RETRAITE => match (true) {
                $anneesRevolues < 5 => null,
                $anneesRevolues === 5 => 3,
                $anneesRevolues <= 10 => 6,
                $anneesRevolues <= 15 => 9,
                $anneesRevolues <= 20 => 12,
                $anneesRevolues <= 25 => 15,
                $anneesRevolues <= 30 => 18,
                $anneesRevolues <= 35 => 21,
                default => 24,
            },
            default => null,
        };
    }
}
