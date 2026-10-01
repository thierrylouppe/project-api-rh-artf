<?php

namespace App\Enums;

enum StatutPlanFormation: string
{
    case BROUILLON = 'brouillon';
    case VALIDE = 'valide';
    case EXECUTE = 'execute';
    case CLOTURE = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::VALIDE => 'Validé',
            self::EXECUTE => 'Exécuté',
            self::CLOTURE => 'Clôturé',
        };
    }

    public function estModifiable(): bool
    {
        return $this === self::BROUILLON;
    }

    public function accepteInscriptions(): bool
    {
        return in_array($this, [self::VALIDE, self::EXECUTE], true);
    }
}
