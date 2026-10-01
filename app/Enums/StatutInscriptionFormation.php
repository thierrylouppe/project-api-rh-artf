<?php

namespace App\Enums;

enum StatutInscriptionFormation: string
{
    case INSCRITE = 'inscrite';
    case PRESENTE = 'presente';
    case TERMINEE = 'terminee';
    case ANNULEE = 'annulee';
    case ABSENTE = 'absente';

    public function label(): string
    {
        return match ($this) {
            self::INSCRITE => 'Inscrite',
            self::PRESENTE => 'Présence confirmée',
            self::TERMINEE => 'Clôturée',
            self::ANNULEE => 'Annulée',
            self::ABSENTE => 'Absente',
        };
    }

    public function estOuverte(): bool
    {
        return in_array($this, [self::INSCRITE, self::PRESENTE], true);
    }
}
