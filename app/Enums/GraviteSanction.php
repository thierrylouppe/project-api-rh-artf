<?php

namespace App\Enums;

enum GraviteSanction: string
{
    case LEGER = 'leger';
    case MOYEN = 'moyen';
    case GRAVE = 'grave';

    public function label(): string
    {
        return match ($this) {
            self::LEGER => 'Léger',
            self::MOYEN => 'Moyen',
            self::GRAVE => 'Grave',
        };
    }
}
