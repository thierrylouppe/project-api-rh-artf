<?php

namespace App\Enums;

enum StatutNomination: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVEE  = 'approuvee';
    case ACTIVE     = 'active';
    case CLOTUREE   = 'cloturee';
    case REJETEE    = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'En attente de validation',
            self::APPROUVEE  => 'Approuvée',
            self::ACTIVE     => 'Active',
            self::CLOTUREE   => 'Clôturée',
            self::REJETEE    => 'Rejetée',
        };
    }

    /** @return list<self> */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::EN_ATTENTE => [self::APPROUVEE, self::REJETEE],
            self::APPROUVEE  => [self::ACTIVE, self::REJETEE],
            self::ACTIVE     => [self::CLOTUREE],
            self::CLOTUREE   => [],
            self::REJETEE    => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    public function estTerminal(): bool
    {
        return in_array($this, [self::CLOTUREE, self::REJETEE], true);
    }
}
