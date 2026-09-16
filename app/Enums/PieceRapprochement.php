<?php

namespace App\Enums;

/**
 * Pièces CCN ARTF art. 81 (rapprochement de conjoints).
 */
enum PieceRapprochement: string
{
    case DEMANDE_MANUSCRITE          = 'demande_manuscrite';
    case ACTE_MARIAGE                = 'acte_mariage';
    case NOTE_AFFECTATION_CONJOINT   = 'note_affectation_conjoint';
    case ATTESTATION_RESIDENCE       = 'attestation_residence';

    public function champFichier(): string
    {
        return 'piece_'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::DEMANDE_MANUSCRITE        => 'Demande manuscrite',
            self::ACTE_MARIAGE              => 'Acte de mariage',
            self::NOTE_AFFECTATION_CONJOINT => 'Note d\'affectation du conjoint',
            self::ATTESTATION_RESIDENCE     => 'Attestation de résidence',
        };
    }

    /** @return list<self> */
    public static function toutes(): array
    {
        return self::cases();
    }
}
