<?php

namespace App\Enums;

enum MotifArchivage: string
{
    case DIMINUTION_ACTIVITE = 'diminution_activite';
    case REORGANISATION      = 'reorganisation';
    case RETRAITE            = 'retraite';
    case DEMISSION           = 'demission';
    case LICENCIEMENT        = 'licenciement';
    case AUTRE               = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::DIMINUTION_ACTIVITE => 'Diminution d\'activité',
            self::REORGANISATION      => 'Réorganisation',
            self::RETRAITE            => 'Retraite',
            self::DEMISSION           => 'Démission',
            self::LICENCIEMENT        => 'Licenciement',
            self::AUTRE               => 'Autre',
        };
    }

    /** Art. 48 : priorité de réembauche 2 ans. */
    public function ouvrePrioriteReembauche(): bool
    {
        return in_array($this, [self::DIMINUTION_ACTIVITE, self::REORGANISATION], true);
    }
}
