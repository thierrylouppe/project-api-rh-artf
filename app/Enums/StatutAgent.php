<?php

namespace App\Enums;

/**
 * Positions administratives d'un agent.
 *
 * Catalogue de valeurs : le modèle Agent ne caste pas ce champ (comparaisons
 * string existantes dans AgentService).
 */
enum StatutAgent: string
{
    case ACTIF                   = 'actif';
    case INACTIF                 = 'inactif';
    case SUSPENDU                = 'suspendu';
    case RETRAITE                = 'retraite';
    case STAGIAIRE               = 'stagiaire';
    case ARCHIVE                 = 'archive';
    case DETACHEMENT             = 'detachement';
    case POSITION_EXCEPTIONNELLE = 'position_exceptionnelle';

    public function label(): string
    {
        return match ($this) {
            self::ACTIF                   => 'Actif',
            self::INACTIF                 => 'Inactif',
            self::SUSPENDU                => 'Suspendu',
            self::RETRAITE                => 'Retraité',
            self::STAGIAIRE               => 'Stagiaire',
            self::ARCHIVE                 => 'Archivé',
            self::DETACHEMENT             => 'En détachement',
            self::POSITION_EXCEPTIONNELLE => 'Position exceptionnelle',
        };
    }

    /** Convention collective ARTF, art. 65 : stage, détachement, position exceptionnelle. */
    public function exempteDeNotation(): bool
    {
        return in_array($this, [
            self::STAGIAIRE,
            self::DETACHEMENT,
            self::POSITION_EXCEPTIONNELLE,
        ], true);
    }

    /** Positions modifiables par la RH via PUT /agents/{id}. */
    public static function modifiablesParRh(): array
    {
        return [
            self::ACTIF->value,
            self::INACTIF->value,
            self::SUSPENDU->value,
            self::RETRAITE->value,
            self::DETACHEMENT->value,
            self::POSITION_EXCEPTIONNELLE->value,
        ];
    }
}
