<?php

namespace App\Enums;

enum TypePriseEnCharge: string
{
    case HONORAIRES_SOINS = 'honoraires_soins';
    case PHARMACEUTIQUE = 'pharmaceutique';
    case VERRES_CORRECTEURS = 'verres_correcteurs';
    case HOSPITALISATION = 'hospitalisation';
    case EVACUATION_SANITAIRE = 'evacuation_sanitaire';

    public function label(): string
    {
        return match ($this) {
            self::HONORAIRES_SOINS => 'Honoraires et soins',
            self::PHARMACEUTIQUE => 'Frais pharmaceutiques',
            self::VERRES_CORRECTEURS => 'Verres correcteurs',
            self::HOSPITALISATION => 'Hospitalisation',
            self::EVACUATION_SANITAIRE => 'Évacuation sanitaire',
        };
    }

    public function articleCcn(): string
    {
        return match ($this) {
            self::HONORAIRES_SOINS => '123',
            self::PHARMACEUTIQUE, self::VERRES_CORRECTEURS => '124',
            self::HOSPITALISATION => '125',
            self::EVACUATION_SANITAIRE => '126',
        };
    }

    /** Part employeur (0–1). */
    public function tauxEmployeur(): float
    {
        return $this === self::PHARMACEUTIQUE ? 0.80 : 1.0;
    }

    public function patientAgentUniquement(): bool
    {
        return $this === self::VERRES_CORRECTEURS;
    }

    public function exigeMontantFacture(): bool
    {
        return $this !== self::EVACUATION_SANITAIRE;
    }

    public function estEvacuation(): bool
    {
        return $this === self::EVACUATION_SANITAIRE;
    }

    /** @return list<TypeStructureSanitaire> */
    public function typesStructure(): array
    {
        return match ($this) {
            self::PHARMACEUTIQUE => [TypeStructureSanitaire::PHARMACIE],
            self::VERRES_CORRECTEURS => [TypeStructureSanitaire::OPTICIEN],
            self::HONORAIRES_SOINS, self::HOSPITALISATION, self::EVACUATION_SANITAIRE => [
                TypeStructureSanitaire::MEDECIN,
                TypeStructureSanitaire::FORMATION_SANITAIRE,
            ],
        };
    }
}
