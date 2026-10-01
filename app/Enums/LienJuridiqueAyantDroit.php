<?php

namespace App\Enums;

enum LienJuridiqueAyantDroit: string
{
    case MARIAGE = 'mariage';
    case UNION_LIBRE = 'union_libre';
    case NATUREL_RECONNU = 'naturel_reconnu';
    case ADOPTION = 'adoption';
    case TUTELLE = 'tutelle';

    public function label(): string
    {
        return match ($this) {
            self::MARIAGE => 'Mariage',
            self::UNION_LIBRE => 'Union libre',
            self::NATUREL_RECONNU => 'Enfant naturel reconnu',
            self::ADOPTION => 'Adoption',
            self::TUTELLE => 'Tutelle',
        };
    }

    /** @return list<self> */
    public static function pourConjoint(): array
    {
        return [self::MARIAGE, self::UNION_LIBRE];
    }

    /** @return list<self> */
    public static function pourEnfant(): array
    {
        return [self::MARIAGE, self::NATUREL_RECONNU, self::ADOPTION, self::TUTELLE];
    }
}
