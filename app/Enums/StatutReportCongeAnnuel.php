<?php

namespace App\Enums;

enum StatutReportCongeAnnuel: string
{
    case PROPOSE = 'propose';
    case ACCORDE = 'accorde';
    case REFUSE  = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSE => 'Proposé',
            self::ACCORDE => 'Accordé',
            self::REFUSE  => 'Refusé',
        };
    }
}
