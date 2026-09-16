<?php

namespace App\Enums;

/**
 * Positions CCN ARTF art. 78–80.
 */
enum TypePositionConventionnelle: string
{
    case DETACHEMENT             = 'detachement';
    case DISPONIBILITE           = 'disponibilite';
    case POSITION_EXCEPTIONNELLE = 'position_exceptionnelle';
    case SOUS_LE_DRAPEAU         = 'sous_le_drapeau';

    public function label(): string
    {
        return match ($this) {
            self::DETACHEMENT             => 'Détachement',
            self::DISPONIBILITE           => 'Mise en disponibilité',
            self::POSITION_EXCEPTIONNELLE => 'Position exceptionnelle',
            self::SOUS_LE_DRAPEAU         => 'Sous le drapeau',
        };
    }

    public function article(): string
    {
        return match ($this) {
            self::DETACHEMENT             => '78',
            self::DISPONIBILITE           => '79',
            self::POSITION_EXCEPTIONNELLE,
            self::SOUS_LE_DRAPEAU         => '80',
        };
    }

    public function statutAgent(): StatutAgent
    {
        return match ($this) {
            self::DETACHEMENT             => StatutAgent::DETACHEMENT,
            self::DISPONIBILITE           => StatutAgent::DISPONIBILITE,
            self::POSITION_EXCEPTIONNELLE => StatutAgent::POSITION_EXCEPTIONNELLE,
            self::SOUS_LE_DRAPEAU         => StatutAgent::SOUS_LE_DRAPEAU,
        };
    }

    public function coupeRemuneration(): bool
    {
        return in_array($this, [self::DETACHEMENT, self::DISPONIBILITE], true);
    }

    public function bloqueAvancementEchelon(): bool
    {
        return $this === self::DISPONIBILITE;
    }

    public function clotureNomination(): bool
    {
        return $this === self::DISPONIBILITE;
    }

    public function ancienneteMinimaleAnnees(): ?int
    {
        return match ($this) {
            self::DETACHEMENT   => 5,
            self::DISPONIBILITE => 3,
            default             => null,
        };
    }

    public function dureeMaximaleAnnees(): ?int
    {
        return match ($this) {
            self::DETACHEMENT   => 5,
            self::DISPONIBILITE => 2,
            default             => null,
        };
    }

    public function preavisMois(): int
    {
        return match ($this) {
            self::DETACHEMENT, self::DISPONIBILITE => 3,
            default => 0,
        };
    }

    public function maxRenouvellements(): ?int
    {
        return match ($this) {
            self::DISPONIBILITE => 2,
            default             => null,
        };
    }

    /** Détachement : indéfini ; disponibilité : 2 fois (art. 78–79). */
    public function peutRenouveler(int $nbDejaEffectues): bool
    {
        if (! in_array($this, [self::DETACHEMENT, self::DISPONIBILITE], true)) {
            return false;
        }

        $max = $this->maxRenouvellements();

        return $max === null || $nbDejaEffectues < $max;
    }
}
