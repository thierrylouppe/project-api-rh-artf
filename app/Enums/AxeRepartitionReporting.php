<?php

namespace App\Enums;

enum AxeRepartitionReporting: string
{
    case DIRECTION = 'direction';
    case GRADE = 'grade';
    case GENRE = 'genre';
    case AGE = 'age';
    case STATUT = 'statut';
    case TYPE_INTEGRATION = 'type_integration';
    case FONCTION = 'fonction';

    public function label(): string
    {
        return match ($this) {
            self::DIRECTION => 'Direction',
            self::GRADE => 'Grade',
            self::GENRE => 'Genre',
            self::AGE => 'Pyramide d\'âge',
            self::STATUT => 'Statut',
            self::TYPE_INTEGRATION => 'Type d\'intégration',
            self::FONCTION => 'Fonction',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
