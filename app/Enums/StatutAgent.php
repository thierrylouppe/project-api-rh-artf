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
    case DISPONIBILITE           = 'disponibilite';   // CCN art. 79
    case SOUS_LE_DRAPEAU         = 'sous_le_drapeau'; // CCN art. 80

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
            self::DISPONIBILITE           => 'En disponibilité',
            self::SOUS_LE_DRAPEAU         => 'Sous le drapeau',
        };
    }

    /**
     * Positions exemptes de notation d'avancement.
     * CCN art. 79 : mise en disponibilité = hors cadre, droits suspendus.
     * CCN art. 80 : sous le drapeau = régime des congés administratifs.
     */
    public function exempteDeNotation(): bool
    {
        return in_array($this, [
            self::STAGIAIRE,
            self::DETACHEMENT,
            self::POSITION_EXCEPTIONNELLE,
            self::DISPONIBILITE,
            self::SOUS_LE_DRAPEAU,
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
            self::DISPONIBILITE->value,
            self::SOUS_LE_DRAPEAU->value,
        ];
    }
}
