<?php

namespace App\Enums;

enum StatutAffectation: string
{
    case EN_ATTENTE_VALIDATION = 'en_attente_validation';
    case APPROUVEE             = 'approuvee';
    case ACTIVE                = 'active';
    case TERMINEE              = 'terminee';
    case REJETEE               = 'rejetee';

    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE_VALIDATION => 'En attente de validation',
            self::APPROUVEE             => 'Approuvée',
            self::ACTIVE                => 'Active',
            self::TERMINEE              => 'Terminée',
            self::REJETEE               => 'Rejetée',
        };
    }

    /** Transitions autorisées depuis ce statut */
    public function transitionsAutorisees(): array
    {
        return match($this) {
            self::EN_ATTENTE_VALIDATION => [self::APPROUVEE, self::REJETEE],
            self::APPROUVEE             => [self::ACTIVE, self::REJETEE],
            self::ACTIVE                => [self::TERMINEE],
            self::TERMINEE              => [],
            self::REJETEE               => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    public function estTerminal(): bool
    {
        return in_array($this, [self::TERMINEE, self::REJETEE], true);
    }
}
