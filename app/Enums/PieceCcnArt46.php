<?php

namespace App\Enums;

/**
 * Pièces CCN ARTF art. 46 (noms stables en base, identiques au seeder).
 */
enum PieceCcnArt46: string
{
    case ACE                = 'Récépissé de l\'agence congolaise pour l\'emploi';
    case ACTE_MARIAGE       = 'Acte de mariage';
    case CARTE_TRAVAIL      = 'Carte de travail';
    case CERTIFICAT_TRAVAIL = 'Certificat de travail (précédent employeur)';
    case NUMERO_CNSS        = 'Numéro d\'immatriculation CNSS';

    /**
     * Toujours exigées pour Recrutement externe / Contractuel (pivot).
     *
     * @return list<self>
     */
    public static function toujoursEmbauche(): array
    {
        return [self::ACE];
    }

    /**
     * Uploadables et éventuellement obligatoires selon le dossier (marié, déjà salarié).
     *
     * @return list<self>
     */
    public static function conditionnelles(): array
    {
        return [
            self::ACTE_MARIAGE,
            self::CARTE_TRAVAIL,
            self::CERTIFICAT_TRAVAIL,
            self::NUMERO_CNSS,
        ];
    }
}
