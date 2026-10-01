<?php

namespace App\Enums;

enum TypeExportReporting: string
{
    case EFFECTIFS = 'effectifs';
    case CONGES = 'conges';
    case EVALUATIONS = 'evaluations';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
