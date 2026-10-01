<?php

namespace App\Enums;

enum TypePiecePrestation: string
{
    case ACTE_DECES = 'acte_deces';
    case FACTURE = 'facture';
    case CERTIFICAT = 'certificat';
    case DECISION_RETRAITE = 'decision_retraite';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ACTE_DECES => 'Acte de décès',
            self::FACTURE => 'Facture',
            self::CERTIFICAT => 'Certificat',
            self::DECISION_RETRAITE => 'Décision de retraite',
            self::AUTRE => 'Autre',
        };
    }
}
