<?php

namespace App\Enums;

enum TypePieceAyantDroit: string
{
    case ACTE_NAISSANCE = 'acte_naissance';
    case ACTE_MARIAGE = 'acte_mariage';
    case JUGEMENT_TUTELLE = 'jugement_tutelle';
    case CERTIFICAT_SCOLARITE = 'certificat_scolarite';
    case CERTIFICAT_APPRENTISSAGE = 'certificat_apprentissage';
    case CERTIFICAT_MEDICAL = 'certificat_medical';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ACTE_NAISSANCE => 'Acte de naissance',
            self::ACTE_MARIAGE => 'Acte de mariage',
            self::JUGEMENT_TUTELLE => 'Jugement de tutelle',
            self::CERTIFICAT_SCOLARITE => 'Certificat de scolarité',
            self::CERTIFICAT_APPRENTISSAGE => 'Certificat d\'apprentissage',
            self::CERTIFICAT_MEDICAL => 'Certificat médical',
            self::AUTRE => 'Autre',
        };
    }
}
