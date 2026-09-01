<?php

namespace App\Enums;

enum StatutDemandeConge: string
{
    case SOUMISE     = 'soumise';
    case VALIDEE_N1  = 'validee_n1';
    case REJETEE_N1  = 'rejetee_n1';
    case VALIDEE_RH  = 'validee_rh';
    case REJETEE_RH  = 'rejetee_rh';
    case VALIDEE_DG  = 'validee_dg';
    case REJETEE_DG  = 'rejetee_dg';

    public function label(): string
    {
        return match ($this) {
            self::SOUMISE     => 'Soumise',
            self::VALIDEE_N1  => 'Validée N+1',
            self::REJETEE_N1  => 'Rejetée N+1',
            self::VALIDEE_RH  => 'Validée RH',
            self::REJETEE_RH  => 'Rejetée RH',
            self::VALIDEE_DG  => 'Validée DG',
            self::REJETEE_DG  => 'Rejetée DG',
        };
    }

    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::SOUMISE    => [self::VALIDEE_N1, self::REJETEE_N1, self::VALIDEE_RH, self::REJETEE_RH],
            self::VALIDEE_N1 => [self::VALIDEE_RH, self::REJETEE_RH, self::VALIDEE_DG, self::REJETEE_DG],
            self::VALIDEE_RH => [self::VALIDEE_DG, self::REJETEE_DG],
            default          => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }
}
