<?php

namespace App\Enums;

enum TypePieceSante: string
{
    case FACTURE = 'facture';
    case ORDONNANCE = 'ordonnance';
    case CERTIFICAT = 'certificat';
    case RAPPORT_MEDICAL = 'rapport_medical';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::FACTURE => 'Facture',
            self::ORDONNANCE => 'Ordonnance',
            self::CERTIFICAT => 'Certificat médical',
            self::RAPPORT_MEDICAL => 'Rapport médical',
            self::AUTRE => 'Autre',
        };
    }
}
