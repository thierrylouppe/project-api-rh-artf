<?php

namespace App\Enums;

enum StatutPaieLot: string
{
    case BROUILLON = 'brouillon';
    case GENERE = 'genere';
    case CONTROLE = 'controle';
    case VALIDE = 'valide';
    case CLOTURE = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::GENERE => 'Généré',
            self::CONTROLE => 'Contrôlé',
            self::VALIDE => 'Validé',
            self::CLOTURE => 'Clôturé',
        };
    }

    public function peutGenerer(): bool
    {
        return in_array($this, [self::BROUILLON, self::GENERE, self::CONTROLE], true);
    }

    public function peutSupprimer(): bool
    {
        return in_array($this, [self::BROUILLON, self::GENERE, self::CONTROLE], true);
    }

    public function peutControler(): bool
    {
        return in_array($this, [self::GENERE, self::CONTROLE], true);
    }

    public function peutValider(): bool
    {
        return $this === self::CONTROLE;
    }

    public function peutCloturer(): bool
    {
        return $this === self::VALIDE;
    }

    public function estVerrouille(): bool
    {
        return in_array($this, [self::VALIDE, self::CLOTURE], true);
    }
}
