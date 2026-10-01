<?php

namespace App\Enums;

enum StatutPrestation: string
{
    case BROUILLON = 'brouillon';
    case SOUMISE = 'soumise';
    case INSTRUITE = 'instruite';
    case ACCORDEE = 'accordee';
    case REFUSEE = 'refusee';
    case CLASSEE = 'classee';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMISE => 'Soumise',
            self::INSTRUITE => 'Instruite',
            self::ACCORDEE => 'Accordée',
            self::REFUSEE => 'Refusée',
            self::CLASSEE => 'Classée',
        };
    }

    public function prochaineEtape(): ?string
    {
        return match ($this) {
            self::BROUILLON => 'soumettre',
            self::SOUMISE => 'instruire',
            self::INSTRUITE => 'accorder',
            default => null,
        };
    }

    public function estModifiable(): bool
    {
        return in_array($this, [self::BROUILLON, self::SOUMISE], true);
    }

    public function estOuverte(): bool
    {
        return in_array($this, [self::BROUILLON, self::SOUMISE, self::INSTRUITE], true);
    }

    public function aDecision(): bool
    {
        return in_array($this, [self::ACCORDEE, self::REFUSEE], true);
    }
}
