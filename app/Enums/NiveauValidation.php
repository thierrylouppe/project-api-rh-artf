<?php

namespace App\Enums;

enum NiveauValidation: string
{
    case CHEF_BUREAU    = 'chef_bureau';
    case CHEF_SERVICE   = 'chef_service';
    case DIRECTEUR      = 'directeur';
    case DRH            = 'drh';
    case DIRECTEUR_GENERAL = 'directeur_general';

    public function label(): string
    {
        return match($this) {
            self::CHEF_BUREAU       => 'Chef de bureau',
            self::CHEF_SERVICE      => 'Chef de service',
            self::DIRECTEUR         => 'Directeur',
            self::DRH               => 'DRH',
            self::DIRECTEUR_GENERAL => 'Directeur Général',
        };
    }

    public function ordre(): int
    {
        return match($this) {
            self::CHEF_BUREAU       => 1,
            self::CHEF_SERVICE      => 2,
            self::DIRECTEUR         => 3,
            self::DRH               => 4,
            self::DIRECTEUR_GENERAL => 5,
        };
    }

    public static function circuitComplet(): array
    {
        return [
            self::CHEF_BUREAU,
            self::CHEF_SERVICE,
            self::DIRECTEUR,
            self::DRH,
            self::DIRECTEUR_GENERAL,
        ];
    }
}
