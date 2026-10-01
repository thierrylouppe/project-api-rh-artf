<?php

namespace App\Enums;

enum MotifAffectation: string
{
    case RAPPROCHEMENT_CONJOINTS = 'rapprochement_conjoints';

    public function label(): string
    {
        return match ($this) {
            self::RAPPROCHEMENT_CONJOINTS => 'Rapprochement de conjoints',
        };
    }

    public static function estRapprochement(?string $code, ?string $motif = null): bool
    {
        return $code === self::RAPPROCHEMENT_CONJOINTS->value
            || $motif === self::RAPPROCHEMENT_CONJOINTS->value;
    }
}
