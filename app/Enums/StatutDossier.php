<?php

namespace App\Enums;

enum StatutDossier: string
{
    case BROUILLON          = 'BROUILLON';
    case SOUMIS             = 'SOUMIS';
    case EN_ETUDE_RH        = 'EN_ETUDE_RH';
    case DOSSIER_INCOMPLET  = 'DOSSIER_INCOMPLET';
    case DOSSIER_COMPLET    = 'DOSSIER_COMPLET';
    case VALIDE_RH          = 'VALIDE_RH';
    case EN_ATTENTE_DG      = 'EN_ATTENTE_DG';
    case VALIDE_DG          = 'VALIDE_DG';
    case ACTE_GENERE        = 'ACTE_GENERE';
    case CONTRAT_SIGNE      = 'CONTRAT_SIGNE';
    case MATRICULE_CREE     = 'MATRICULE_CREE';
    case AFFECTE            = 'AFFECTE';
    case NOMME              = 'NOMME';
    case COMPTE_CREE        = 'COMPTE_CREE';
    case PRISE_DE_SERVICE   = 'PRISE_DE_SERVICE';
    case INTEGRE            = 'INTEGRE';
    case SUSPENDU           = 'SUSPENDU';
    case REJETE             = 'REJETE';
    case ANNULE             = 'ANNULE';

    public function label(): string
    {
        return match($this) {
            self::BROUILLON         => 'Brouillon',
            self::SOUMIS            => 'Soumis',
            self::EN_ETUDE_RH       => 'En étude RH',
            self::DOSSIER_INCOMPLET => 'Dossier incomplet',
            self::DOSSIER_COMPLET   => 'Dossier complet',
            self::VALIDE_RH         => 'Validé RH',
            self::EN_ATTENTE_DG     => 'En attente DG',
            self::VALIDE_DG         => 'Validé DG',
            self::ACTE_GENERE       => 'Acte généré',
            self::CONTRAT_SIGNE     => 'Contrat signé',
            self::MATRICULE_CREE    => 'Matricule créé',
            self::AFFECTE           => 'Affecté',
            self::NOMME             => 'Nommé',
            self::COMPTE_CREE       => 'Compte créé',
            self::PRISE_DE_SERVICE  => 'Prise de service',
            self::INTEGRE           => 'Intégré',
            self::SUSPENDU          => 'Suspendu',
            self::REJETE            => 'Rejeté',
            self::ANNULE            => 'Annulé',
        };
    }

    /** Transitions autorisées depuis ce statut */
    public function transitionsAutorisees(): array
    {
        return match($this) {
            self::BROUILLON         => [self::SOUMIS, self::ANNULE],
            self::SOUMIS            => [self::EN_ETUDE_RH, self::REJETE, self::ANNULE],
            self::EN_ETUDE_RH       => [self::DOSSIER_INCOMPLET, self::DOSSIER_COMPLET, self::REJETE],
            self::DOSSIER_INCOMPLET => [self::EN_ETUDE_RH, self::ANNULE],
            self::DOSSIER_COMPLET   => [self::VALIDE_RH, self::REJETE],
            self::VALIDE_RH         => [self::EN_ATTENTE_DG, self::REJETE],
            self::EN_ATTENTE_DG     => [self::VALIDE_DG, self::REJETE],
            self::VALIDE_DG         => [self::ACTE_GENERE],
            self::ACTE_GENERE       => [self::CONTRAT_SIGNE, self::MATRICULE_CREE],
            self::CONTRAT_SIGNE     => [self::MATRICULE_CREE],
            self::MATRICULE_CREE    => [self::AFFECTE],
            self::AFFECTE           => [self::NOMME, self::COMPTE_CREE],
            self::NOMME             => [self::COMPTE_CREE],
            self::COMPTE_CREE       => [self::PRISE_DE_SERVICE],
            self::PRISE_DE_SERVICE  => [self::INTEGRE],
            default                 => [],
        };
    }

    public function peutTransitionnerVers(self $cible): bool
    {
        return in_array($cible, $this->transitionsAutorisees(), true);
    }

    public function estTerminal(): bool
    {
        return in_array($this, [self::INTEGRE, self::REJETE, self::ANNULE], true);
    }
}
