<?php

namespace App\Enums;

enum StatutSanction: string
{
    case EN_ATTENTE = 'en_attente';
    case INSTRUITE = 'instruite';
    case VALIDEE = 'validee';
    case REJETEE = 'rejetee';

    public function label(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'Rapport soumis',
            self::INSTRUITE => 'Instruite — à prononcer',
            self::VALIDEE => 'Prononcée',
            self::REJETEE => 'Classée sans suite',
        };
    }

    public function estOuvert(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::INSTRUITE], true);
    }

    public function prochaineEtape(): ?string
    {
        return match ($this) {
            self::EN_ATTENTE => 'instruire',
            self::INSTRUITE => 'prononcer',
            self::VALIDEE, self::REJETEE => null,
        };
    }
}
