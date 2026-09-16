<?php

namespace App\Enums;

enum CodePaieElement: string
{
    case SALAIRE_FONCTIONNEL = 'salaire_fonctionnel';
    case PRIME_RESPONSABILITE = 'prime_responsabilite';
    case PRIME_ANCIENNETE = 'prime_anciennete';
    case PRIME_REPRESENTATION = 'prime_representation';
    case PRIME_FIN_ANNEE = 'prime_fin_annee';
    case PRIME_RISQUE = 'prime_risque';
    case PRIME_VESTIMENTAIRE = 'prime_vestimentaire';
    case PRIME_CAISSE = 'prime_caisse';
    case PRIME_PANIER = 'prime_panier';
    case PRIME_ASTREINTE = 'prime_astreinte';
    case PRIME_LOGEMENT = 'prime_logement';
    case PRIME_EXCEPTIONNELLE = 'prime_exceptionnelle';
    case PRIME_TRANSPORT_STAGIAIRE = 'prime_transport_stagiaire';
    case INDEMNITE_TRANSPORT = 'indemnite_transport';
    case INDEMNITE_DEPLACEMENT_AFFECTATION = 'indemnite_deplacement_affectation';
    case INDEMNITE_DEPLACEMENT_CONGES = 'indemnite_deplacement_conges';
    case INDEMNITE_INTERIM = 'indemnite_interim';
    case INDEMNITE_FORMATION = 'indemnite_formation';
    case INDEMNITE_MISSION_LOCALE = 'indemnite_mission_locale';
    case INDEMNITE_MISSION_ETRANGER = 'indemnite_mission_etranger';
    case ALLOCATION_RENTREE_SCOLAIRE = 'allocation_rentree_scolaire';
    case ALLOCATION_CONSOMMATION = 'allocation_consommation';
    case ALLOCATION_ARBRE_NOEL = 'allocation_arbre_noel';
    case ALLOCATIONS_FAMILIALES = 'allocations_familiales';
    case SUPPLEMENT_FAMILIAL = 'supplement_familial';
    case RETENUE_CNSS = 'retenue_cnss';
    case RETENUE_AUTRE = 'retenue_autre';
    case INDEMNITE_RETRAITE = 'indemnite_retraite';
    case CAPITAL_DECES = 'capital_deces';

    /**
     * @return array{
     *     libelle: string,
     *     nature: NaturePaieElement,
     *     periodicite: PeriodicitePaieElement,
     *     mode_calcul: ModeCalculPaieElement,
     *     article_ccn: ?string,
     *     fonction_sigles: ?list<string>,
     *     mois_declenchement: ?list<int>,
     *     actif: bool
     * }
     */
    public function meta(): array
    {
        return match ($this) {
            self::SALAIRE_FONCTIONNEL => $this->row('Salaire fonctionnel', NaturePaieElement::SALAIRE_FONCTIONNEL, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '55', ['DG', 'DC', 'DD']),
            self::PRIME_RESPONSABILITE => $this->row('Prime de responsabilité', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '56', ['CB', 'CS', 'CSR', 'DD', 'DC', 'DG']),
            self::PRIME_ANCIENNETE => $this->row('Prime d\'ancienneté', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::FORMULE_CCN, '56'),
            self::PRIME_REPRESENTATION => $this->row('Prime de représentation', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '56', ['DD']),
            self::PRIME_FIN_ANNEE => $this->row('Prime de fin d\'année', NaturePaieElement::PRIME, PeriodicitePaieElement::ANNUEL, ModeCalculPaieElement::FORMULE_CCN, '56', null, [12]),
            self::PRIME_RISQUE => $this->row('Prime de risque', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '56'),
            self::PRIME_VESTIMENTAIRE => $this->row('Prime vestimentaire', NaturePaieElement::PRIME, PeriodicitePaieElement::SEMESTRIEL, ModeCalculPaieElement::MONTANT_FIXE, '56', null, [6, 12]),
            self::PRIME_CAISSE => $this->row('Prime de caisse', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '56'),
            self::PRIME_PANIER => $this->row('Prime de panier', NaturePaieElement::PRIME, PeriodicitePaieElement::JOURNALIER, ModeCalculPaieElement::MONTANT_FIXE, '56'),
            self::PRIME_ASTREINTE => $this->row('Prime d\'astreinte', NaturePaieElement::PRIME, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::MONTANT_FIXE, '56'),
            self::PRIME_LOGEMENT => $this->row('Prime de logement', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '56'),
            self::PRIME_EXCEPTIONNELLE => $this->row('Prime exceptionnelle', NaturePaieElement::PRIME, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::MONTANT_FIXE, '56'),
            self::PRIME_TRANSPORT_STAGIAIRE => $this->row('Prime de transport stagiaire', NaturePaieElement::PRIME, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '54'),
            self::INDEMNITE_TRANSPORT => $this->row('Indemnité de transport', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '57'),
            self::INDEMNITE_DEPLACEMENT_AFFECTATION => $this->row('Indemnité de déplacement (affectation)', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::MONTANT_FIXE, '57'),
            self::INDEMNITE_DEPLACEMENT_CONGES => $this->row('Indemnité de déplacement (congés)', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::MONTANT_FIXE, '57'),
            self::INDEMNITE_INTERIM => $this->row('Indemnité d\'intérim', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '57'),
            self::INDEMNITE_FORMATION => $this->row('Indemnité de formation', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::FORMULE_CCN, '57'),
            self::INDEMNITE_MISSION_LOCALE => $this->row('Indemnité de mission locale', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::JOURNALIER, ModeCalculPaieElement::BAREME_CCN, '57'),
            self::INDEMNITE_MISSION_ETRANGER => $this->row('Indemnité de mission à l\'étranger', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::JOURNALIER, ModeCalculPaieElement::BAREME_CCN, '57'),
            self::ALLOCATION_RENTREE_SCOLAIRE => $this->row('Allocation rentrée scolaire', NaturePaieElement::ALLOCATION, PeriodicitePaieElement::ANNUEL, ModeCalculPaieElement::MONTANT_FIXE, '58', null, [9]),
            self::ALLOCATION_CONSOMMATION => $this->row('Allocation de consommation domestique', NaturePaieElement::ALLOCATION, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '58', ['DC', 'DD']),
            self::ALLOCATION_ARBRE_NOEL => $this->row('Allocation arbre de Noël', NaturePaieElement::ALLOCATION, PeriodicitePaieElement::ANNUEL, ModeCalculPaieElement::MONTANT_FIXE, '58', null, [12]),
            self::ALLOCATIONS_FAMILIALES => $this->row('Allocations familiales', NaturePaieElement::ALLOCATION, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '59'),
            self::SUPPLEMENT_FAMILIAL => $this->row('Supplément familial de traitement', NaturePaieElement::ALLOCATION, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::MONTANT_FIXE, '59'),
            self::RETENUE_CNSS => $this->row('Retenue CNSS', NaturePaieElement::RETENUE, PeriodicitePaieElement::MENSUEL, ModeCalculPaieElement::POURCENTAGE_BASE, null),
            self::RETENUE_AUTRE => $this->row('Retenue (acompte, prêt…)', NaturePaieElement::RETENUE, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::MONTANT_FIXE, null),
            self::INDEMNITE_RETRAITE => $this->row('Indemnité d\'admission à la retraite', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::FORMULE_CCN, '119', null, null, false),
            self::CAPITAL_DECES => $this->row('Capital décès', NaturePaieElement::INDEMNITE, PeriodicitePaieElement::PONCTUEL, ModeCalculPaieElement::FORMULE_CCN, '121', null, null, false),
        };
    }

    /**
     * @param  list<string>|null  $fonctionSigles
     * @param  list<int>|null  $moisDeclenchement
     * @return array{
     *     libelle: string,
     *     nature: NaturePaieElement,
     *     periodicite: PeriodicitePaieElement,
     *     mode_calcul: ModeCalculPaieElement,
     *     article_ccn: ?string,
     *     fonction_sigles: ?list<string>,
     *     mois_declenchement: ?list<int>,
     *     actif: bool
     * }
     */
    private function row(
        string $libelle,
        NaturePaieElement $nature,
        PeriodicitePaieElement $periodicite,
        ModeCalculPaieElement $modeCalcul,
        ?string $articleCcn,
        ?array $fonctionSigles = null,
        ?array $moisDeclenchement = null,
        bool $actif = true,
    ): array {
        return [
            'libelle' => $libelle,
            'nature' => $nature,
            'periodicite' => $periodicite,
            'mode_calcul' => $modeCalcul,
            'article_ccn' => $articleCcn,
            'fonction_sigles' => $fonctionSigles,
            'mois_declenchement' => $moisDeclenchement,
            'actif' => $actif,
        ];
    }

    public function libelle(): string
    {
        return $this->meta()['libelle'];
    }

    /** Versé par le moteur de lot, pas par une affectation RH. */
    public function estCalculeAuto(): bool
    {
        return in_array($this, [
            self::PRIME_ANCIENNETE,
            self::PRIME_FIN_ANNEE,
            self::ALLOCATION_RENTREE_SCOLAIRE,
            self::ALLOCATION_ARBRE_NOEL,
        ], true);
    }
}
