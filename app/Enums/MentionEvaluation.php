<?php

namespace App\Enums;

/**
 * Mentions attribuées selon la note finale sur 20.
 * Barème CCN ARTF / grille interne.
 */
enum MentionEvaluation: string
{
    case EXCELLENT    = 'Excellent';
    case TRES_BIEN    = 'Très bien';
    case BIEN         = 'Bien';
    case MOYEN        = 'Moyen';
    case INSUFFISANT  = 'Insuffisant';

    public static function depuisNote(float $note): self
    {
        return match (true) {
            $note >= 16.0 => self::EXCELLENT,
            $note >= 14.0 => self::TRES_BIEN,
            $note >= 12.0 => self::BIEN,
            $note >= 10.0 => self::MOYEN,
            default       => self::INSUFFISANT,
        };
    }

    public function label(): string
    {
        return $this->value;
    }
}
