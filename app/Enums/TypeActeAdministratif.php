<?php

namespace App\Enums;

enum TypeActeAdministratif: string
{
    case DECISION_RECRUTEMENT  = 'decision_recrutement';
    case CONTRAT               = 'contrat';
    case DECISION_MUTATION     = 'decision_mutation';
    case ARRETE_DETACHEMENT    = 'arrete_detachement';
    case DECISION_AFFECTATION  = 'decision_affectation';
    case DECISION_NOMINATION   = 'decision_nomination';
    case PV_PRISE_DE_SERVICE   = 'pv_prise_de_service';
    case NOTE_DE_SERVICE       = 'note_de_service';

    public function label(): string
    {
        return match($this) {
            self::DECISION_RECRUTEMENT  => 'Décision de recrutement',
            self::CONTRAT               => 'Contrat de travail',
            self::DECISION_MUTATION     => 'Décision de mutation',
            self::ARRETE_DETACHEMENT    => 'Arrêté de détachement',
            self::DECISION_AFFECTATION  => 'Décision d\'affectation',
            self::DECISION_NOMINATION   => 'Décision de nomination',
            self::PV_PRISE_DE_SERVICE   => 'Procès-verbal de prise de service',
            self::NOTE_DE_SERVICE       => 'Note de service',
        };
    }

    public function prefixeNumero(): string
    {
        return match($this) {
            self::DECISION_RECRUTEMENT  => 'REC',
            self::CONTRAT               => 'CTR',
            self::DECISION_MUTATION     => 'MUT',
            self::ARRETE_DETACHEMENT    => 'DET',
            self::DECISION_AFFECTATION  => 'AFF',
            self::DECISION_NOMINATION   => 'NOM',
            self::PV_PRISE_DE_SERVICE   => 'PVS',
            self::NOTE_DE_SERVICE       => 'NDS',
        };
    }
}
