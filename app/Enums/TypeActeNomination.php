<?php

namespace App\Enums;

enum TypeActeNomination: string
{
    case ARRETE       = 'arrete';
    case DECISION     = 'decision';
    case NOTE_SERVICE = 'note_service';

    public function label(): string
    {
        return match ($this) {
            self::ARRETE       => 'Arrêté de nomination',
            self::DECISION     => TypeActeAdministratif::DECISION_NOMINATION->label(),
            self::NOTE_SERVICE => TypeActeAdministratif::NOTE_DE_SERVICE->label(),
        };
    }

    public function prefixeNumero(): string
    {
        return match ($this) {
            self::ARRETE       => 'ARR',
            self::DECISION     => TypeActeAdministratif::DECISION_NOMINATION->prefixeNumero(),
            self::NOTE_SERVICE => TypeActeAdministratif::NOTE_DE_SERVICE->prefixeNumero(),
        };
    }

    public function titreDocument(): string
    {
        return match ($this) {
            self::ARRETE       => 'Arrêté',
            self::DECISION     => 'Décision',
            self::NOTE_SERVICE => 'Note de service',
        };
    }
}
