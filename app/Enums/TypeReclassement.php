<?php

namespace App\Enums;

enum TypeReclassement: string
{
    case RECLASSEMENT_FORMATION    = 'reclassement_formation';
    case RECLASSEMENT_EXCEPTIONNEL = 'reclassement_exceptionnel';
    case HORS_CLASSE               = 'hors_classe';
    case RECONVERSION              = 'reconversion';

    public function label(): string
    {
        return match ($this) {
            self::RECLASSEMENT_FORMATION    => 'Reclassement après formation (art. 73)',
            self::RECLASSEMENT_EXCEPTIONNEL => 'Reclassement exceptionnel (art. 74)',
            self::HORS_CLASSE               => 'Hors classe (art. 74)',
            self::RECONVERSION              => 'Reconversion (art. 75)',
        };
    }

    public function article(): string
    {
        return match ($this) {
            self::RECLASSEMENT_FORMATION    => '73',
            self::RECLASSEMENT_EXCEPTIONNEL, self::HORS_CLASSE => '74',
            self::RECONVERSION              => '75',
        };
    }

    /** 74a, 74b et 75 : le DG approuve. Art. 73 : la RH. */
    public function exigeApprobationDg(): bool
    {
        return $this !== self::RECLASSEMENT_FORMATION;
    }

    public function changeLaClasse(): bool
    {
        return $this !== self::RECONVERSION;
    }

    public function typeChangementSalaire(): \App\Enums\TypeChangementSalaireAgent
    {
        return match ($this) {
            self::RECLASSEMENT_FORMATION,
            self::RECLASSEMENT_EXCEPTIONNEL => \App\Enums\TypeChangementSalaireAgent::RECLASSEMENT,
            self::HORS_CLASSE               => \App\Enums\TypeChangementSalaireAgent::HORS_CLASSE,
            self::RECONVERSION              => \App\Enums\TypeChangementSalaireAgent::RECONVERSION,
        };
    }
}
