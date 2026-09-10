<?php

namespace App\Enums;

/**
 * Décision de la commission d'avancement (CCN ARTF art. 70).
 *
 * - Favorable   : avancement accordé (nombre_echelons ≥ 1, même classe)
 * - Défavorable : avancement refusé (nombre_echelons = 0)
 * - Reporté     : décision différée à la prochaine session
 */
enum DecisionCommission: string
{
    case FAVORABLE   = 'favorable';
    case DEFAVORABLE = 'defavorable';
    case REPORTE     = 'reporte';

    public function label(): string
    {
        return match ($this) {
            self::FAVORABLE   => 'Favorable (avancement accordé)',
            self::DEFAVORABLE => 'Défavorable (avancement refusé)',
            self::REPORTE     => 'Reporté (différé à la prochaine session)',
        };
    }

    public function donneDroitAvancement(): bool
    {
        return $this === self::FAVORABLE;
    }
}
