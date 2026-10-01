<?php

namespace App\Enums;

enum ModaliteFormation: string
{
    case INTERNE = 'interne';
    case EXTERNE = 'externe';

    public function label(): string
    {
        return match ($this) {
            self::INTERNE => 'Interne',
            self::EXTERNE => 'Externe',
        };
    }
}
