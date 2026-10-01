<?php

namespace App\Enums;

/**
 * Niveaux hiérarchiques pour les avis (CCN ARTF art. 64).
 *
 * Chaîne standard : chef_bureau(1) → chef_service(2) → directeur(3) → directeur_general(4)
 * Chaîne DG       : chef_bureau(1) → chef_service(2) → directeur_general(3)
 */
enum NiveauAvisHierarchique: string
{
    case CHEF_BUREAU       = 'chef_bureau';
    case CHEF_SERVICE      = 'chef_service';
    case DIRECTEUR         = 'directeur';
    case DIRECTEUR_GENERAL = 'directeur_general';

    public function label(): string
    {
        return match ($this) {
            self::CHEF_BUREAU       => 'Chef de Bureau',
            self::CHEF_SERVICE      => 'Chef de Service',
            self::DIRECTEUR         => 'Directeur',
            self::DIRECTEUR_GENERAL => 'Directeur Général',
        };
    }

    /** Ordre dans la chaîne standard (sans variante DG). */
    public function ordreStandard(): int
    {
        return match ($this) {
            self::CHEF_BUREAU       => 1,
            self::CHEF_SERVICE      => 2,
            self::DIRECTEUR         => 3,
            self::DIRECTEUR_GENERAL => 4,
        };
    }
}
