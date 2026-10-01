<?php

namespace App\Enums;

/**
 * Période d'essai CCN ARTF art. 49 (CDI / CDD uniquement).
 */
enum StatutEssai: string
{
    case EN_COURS        = 'en_cours';
    case RENOUVELE       = 'renouvele';
    case CONCLUANT       = 'concluant';
    case ROMPU           = 'rompu';
    case NON_APPLICABLE  = 'non_applicable';

    public function label(): string
    {
        return match ($this) {
            self::EN_COURS       => 'En cours',
            self::RENOUVELE      => 'Renouvelée',
            self::CONCLUANT      => 'Concluante',
            self::ROMPU          => 'Rompue',
            self::NON_APPLICABLE => 'Non applicable',
        };
    }

    public function estOuvert(): bool
    {
        return in_array($this, [self::EN_COURS, self::RENOUVELE], true);
    }
}
