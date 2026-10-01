<?php

namespace App\Enums;

enum SensPaieElement: string
{
    case GAIN = 'gain';
    case RETENUE = 'retenue';

    public function label(): string
    {
        return match ($this) {
            self::GAIN => 'Gain',
            self::RETENUE => 'Retenue',
        };
    }
}
