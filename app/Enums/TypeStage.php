<?php

namespace App\Enums;

enum TypeStage: string
{
    case ACADEMIQUE      = 'academique';
    case PROFESSIONNEL   = 'professionnel';
    case QUALIFICATION   = 'qualification';

    public function label(): string
    {
        return match ($this) {
            self::ACADEMIQUE    => 'Stage académique',
            self::PROFESSIONNEL => 'Stage professionnel',
            self::QUALIFICATION => 'Stage de qualification',
        };
    }

    public function prefixeMatricule(): string
    {
        return 'STG';
    }
}
