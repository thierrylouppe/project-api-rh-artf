<?php

namespace App\Enums;

enum NaturePaieElement: string
{
    case PRIME = 'prime';
    case INDEMNITE = 'indemnite';
    case ALLOCATION = 'allocation';
    case RETENUE = 'retenue';
    case SALAIRE_FONCTIONNEL = 'salaire_fonctionnel';

    public function label(): string
    {
        return match ($this) {
            self::PRIME => 'Prime',
            self::INDEMNITE => 'Indemnité',
            self::ALLOCATION => 'Allocation',
            self::RETENUE => 'Retenue',
            self::SALAIRE_FONCTIONNEL => 'Salaire fonctionnel',
        };
    }

    public function sens(): SensPaieElement
    {
        return $this === self::RETENUE
            ? SensPaieElement::RETENUE
            : SensPaieElement::GAIN;
    }
}
