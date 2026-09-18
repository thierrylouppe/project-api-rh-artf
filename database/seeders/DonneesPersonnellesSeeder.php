<?php

namespace Database\Seeders;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\QualiteAgeAyantDroit;
use App\Enums\TypeAyantDroit;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\ContactUrgence;
use App\Models\InformationsPersonnelle;
use App\Models\SituationFamiliale;
use Illuminate\Database\Seeder;

/**
 * DonneesPersonnellesSeeder
 *
 * Alimente pour chaque agent :
 *   • InformationsPersonnelles  (adresse, ville, quartier)
 *   • SituationFamiliale        (statut matrimonial, nb_enfants)
 *   • AyantsDroits              (conjoint + enfants)
 *   • ContactUrgence
 */
class DonneesPersonnellesSeeder extends Seeder
{
    // Données fixes par matricule : [statut_matrimonial, nb_enfants, conjoint?, enfants[]]
    // Priorité aux agents avec responsabilité pour plus de richesse dans les données
    private const PERSONAS = [
        // ── DG ──────────────────────────────────────────────────────────
        'ARFT-00001' => [
            'adresse' => '12 Avenue de l\'Indépendance', 'quartier' => 'Bacongo', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'MOUKALA', 'prenom' => 'Clarisse', 'sexe' => 'F', 'ddn' => '1968-07-22'],
            'enfants' => [
                ['nom' => 'MOUKALA', 'prenom' => 'Éric',    'sexe' => 'M', 'ddn' => '1998-03-10', 'qualite' => 'etudes'],
                ['nom' => 'MOUKALA', 'prenom' => 'Vanessa', 'sexe' => 'F', 'ddn' => '2001-11-25', 'qualite' => 'etudes'],
                ['nom' => 'MOUKALA', 'prenom' => 'Nathan',  'sexe' => 'M', 'ddn' => '2012-06-05', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MOUKALA', 'prenom' => 'Clarisse', 'tel' => '+242 06 123 45 67', 'relation' => 'Épouse'],
        ],
        // ── D.R.H.L ─────────────────────────────────────────────────────
        'ARFT-00017' => [
            'adresse' => '45 Rue Mpila', 'quartier' => 'Poto-Poto', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'GAMBOU', 'prenom' => 'André', 'sexe' => 'M', 'ddn' => '1969-05-14'],
            'enfants' => [
                ['nom' => 'GAMBOU', 'prenom' => 'Sophie', 'sexe' => 'F', 'ddn' => '2000-08-18', 'qualite' => 'etudes'],
                ['nom' => 'GAMBOU', 'prenom' => 'David',  'sexe' => 'M', 'ddn' => '2005-02-09', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'GAMBOU', 'prenom' => 'André', 'tel' => '+242 06 234 56 78', 'relation' => 'Époux'],
        ],
        'ARFT-00018' => [
            'adresse' => '18 Rue de la Paix', 'quartier' => 'Ouenzé', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 4,
            'conjoint' => ['nom' => 'MILANDOU', 'prenom' => 'Joëlle', 'sexe' => 'F', 'ddn' => '1979-09-30'],
            'enfants' => [
                ['nom' => 'MILANDOU', 'prenom' => 'Kevin',   'sexe' => 'M', 'ddn' => '2004-01-17', 'qualite' => 'standard'],
                ['nom' => 'MILANDOU', 'prenom' => 'Priscille','sexe' => 'F', 'ddn' => '2006-07-23', 'qualite' => 'standard'],
                ['nom' => 'MILANDOU', 'prenom' => 'Théo',    'sexe' => 'M', 'ddn' => '2010-11-04', 'qualite' => 'standard'],
                ['nom' => 'MILANDOU', 'prenom' => 'Lola',    'sexe' => 'F', 'ddn' => '2015-03-12', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MILANDOU', 'prenom' => 'Joëlle', 'tel' => '+242 06 345 67 89', 'relation' => 'Épouse'],
        ],
        'ARFT-00019' => [
            'adresse' => '7 Avenue des Trois Martyrs', 'quartier' => 'Makélékélé', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'MOUNANGA', 'prenom' => 'Paul', 'tel' => '+242 06 456 78 90', 'relation' => 'Père'],
        ],
        'ARFT-00020' => [
            'adresse' => '23 Rue Moungali', 'quartier' => 'Moungali', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 1,
            'enfants' => [
                ['nom' => 'LOUBAKI', 'prenom' => 'Janis', 'sexe' => 'F', 'ddn' => '2018-05-20', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'LOUBAKI', 'prenom' => 'Marie', 'tel' => '+242 06 567 89 01', 'relation' => 'Mère'],
        ],
        'ARFT-00021' => [
            'adresse' => '31 Avenue Matsoua', 'quartier' => 'Talangaï', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'NKOUNKOU', 'prenom' => 'Charles', 'sexe' => 'M', 'ddn' => '1987-04-15'],
            'enfants' => [
                ['nom' => 'NKOUNKOU', 'prenom' => 'Ange',    'sexe' => 'M', 'ddn' => '2014-09-08', 'qualite' => 'standard'],
                ['nom' => 'NKOUNKOU', 'prenom' => 'Christel','sexe' => 'F', 'ddn' => '2017-01-30', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NKOUNKOU', 'prenom' => 'Charles', 'tel' => '+242 06 678 90 12', 'relation' => 'Époux'],
        ],
        // ── D.G (Direction Générale) ─────────────────────────────────────
        'ARFT-00002' => [
            'adresse' => '5 Rue du Marché', 'quartier' => 'Centre-ville', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'NZABA', 'prenom' => 'Félix', 'sexe' => 'M', 'ddn' => '1970-03-18'],
            'enfants' => [
                ['nom' => 'NZABA', 'prenom' => 'Laura', 'sexe' => 'F', 'ddn' => '2000-06-10', 'qualite' => 'etudes'],
                ['nom' => 'NZABA', 'prenom' => 'Jules', 'sexe' => 'M', 'ddn' => '2003-12-25', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NZABA', 'prenom' => 'Félix', 'tel' => '+242 06 789 01 23', 'relation' => 'Époux'],
        ],
        'ARFT-00003' => [
            'adresse' => '14 Rue Congo', 'quartier' => 'Bacongo', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'NGOMA', 'prenom' => 'Henriette', 'sexe' => 'F', 'ddn' => '1980-07-22'],
            'enfants' => [
                ['nom' => 'NGOMA', 'prenom' => 'Aurélien','sexe' => 'M', 'ddn' => '2006-04-14', 'qualite' => 'standard'],
                ['nom' => 'NGOMA', 'prenom' => 'Céleste', 'sexe' => 'F', 'ddn' => '2009-08-30', 'qualite' => 'standard'],
                ['nom' => 'NGOMA', 'prenom' => 'Rodrigo', 'sexe' => 'M', 'ddn' => '2013-02-19', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NGOMA', 'prenom' => 'Henriette', 'tel' => '+242 05 111 22 33', 'relation' => 'Épouse'],
        ],
        'ARFT-00004' => [
            'adresse' => '9 Avenue Kasai', 'quartier' => 'Ouenzé', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'MBEMBA', 'prenom' => 'Rose', 'tel' => '+242 05 222 33 44', 'relation' => 'Sœur'],
        ],
        'ARFT-00005' => [
            'adresse' => '27 Rue Behagle', 'quartier' => 'Poto-Poto', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 1,
            'conjoint' => ['nom' => 'LOEMBA', 'prenom' => 'Gaston', 'sexe' => 'M', 'ddn' => '1988-02-11'],
            'enfants' => [
                ['nom' => 'LOEMBA', 'prenom' => 'Alexia', 'sexe' => 'F', 'ddn' => '2016-07-04', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'LOEMBA', 'prenom' => 'Gaston', 'tel' => '+242 05 333 44 55', 'relation' => 'Époux'],
        ],
        'ARFT-00006' => [
            'adresse' => '3 Rue Mbemba', 'quartier' => 'Makélékélé', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'KIBANGOU', 'prenom' => 'Marie-Claire', 'tel' => '+242 05 444 55 66', 'relation' => 'Mère'],
        ],
        // ── D.F ─────────────────────────────────────────────────────────
        'ARFT-00007' => [
            'adresse' => '88 Boulevard Denis Sassou Nguesso', 'quartier' => 'Centre-ville', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 4,
            'conjoint' => ['nom' => 'BIYOUDI', 'prenom' => 'Laurence', 'sexe' => 'F', 'ddn' => '1972-11-05'],
            'enfants' => [
                ['nom' => 'BIYOUDI', 'prenom' => 'Boris',   'sexe' => 'M', 'ddn' => '1999-01-28', 'qualite' => 'etudes'],
                ['nom' => 'BIYOUDI', 'prenom' => 'Sandra',  'sexe' => 'F', 'ddn' => '2002-09-17', 'qualite' => 'etudes'],
                ['nom' => 'BIYOUDI', 'prenom' => 'Loïc',    'sexe' => 'M', 'ddn' => '2007-04-03', 'qualite' => 'standard'],
                ['nom' => 'BIYOUDI', 'prenom' => 'Gisèle',  'sexe' => 'F', 'ddn' => '2011-12-14', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'BIYOUDI', 'prenom' => 'Laurence', 'tel' => '+242 06 555 66 77', 'relation' => 'Épouse'],
        ],
        'ARFT-00008' => [
            'adresse' => '55 Rue de l\'Imprimerie', 'quartier' => 'Bacongo', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'ONDONGO', 'prenom' => 'Thierry', 'sexe' => 'M', 'ddn' => '1974-10-12'],
            'enfants' => [
                ['nom' => 'ONDONGO', 'prenom' => 'Mélodie', 'sexe' => 'F', 'ddn' => '2008-05-22', 'qualite' => 'standard'],
                ['nom' => 'ONDONGO', 'prenom' => 'Yann',    'sexe' => 'M', 'ddn' => '2011-11-07', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'ONDONGO', 'prenom' => 'Thierry', 'tel' => '+242 06 666 77 88', 'relation' => 'Époux'],
        ],
        'ARFT-00009' => [
            'adresse' => '12 Cité des Fonctionnaires', 'quartier' => 'Moungali', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 1,
            'enfants' => [
                ['nom' => 'NSIMBA', 'prenom' => 'Théophile', 'sexe' => 'M', 'ddn' => '2015-02-28', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NSIMBA', 'prenom' => 'Virginie', 'tel' => '+242 06 777 88 99', 'relation' => 'Sœur'],
        ],
        'ARFT-00010' => [
            'adresse' => '40 Rue des Flamboyants', 'quartier' => 'Talangaï', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'MOUAMBA', 'prenom' => 'Guy', 'sexe' => 'M', 'ddn' => '1989-08-19'],
            'enfants' => [
                ['nom' => 'MOUAMBA', 'prenom' => 'Inès',  'sexe' => 'F', 'ddn' => '2014-04-06', 'qualite' => 'standard'],
                ['nom' => 'MOUAMBA', 'prenom' => 'Malo',  'sexe' => 'M', 'ddn' => '2016-10-13', 'qualite' => 'standard'],
                ['nom' => 'MOUAMBA', 'prenom' => 'Lucie', 'sexe' => 'F', 'ddn' => '2019-08-01', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MOUAMBA', 'prenom' => 'Guy', 'tel' => '+242 05 888 99 00', 'relation' => 'Époux'],
        ],
        'ARFT-00011' => [
            'adresse' => '16 Avenue de la Paix', 'quartier' => 'Ouenzé', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'NKAYA', 'prenom' => 'Joséphine', 'tel' => '+242 05 999 00 11', 'relation' => 'Mère'],
        ],
        // ── D.R ─────────────────────────────────────────────────────────
        'ARFT-00012' => [
            'adresse' => '2 Rue du Stade', 'quartier' => 'Bacongo', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'MAVOUNGOU', 'prenom' => 'Henri', 'sexe' => 'M', 'ddn' => '1971-12-08'],
            'enfants' => [
                ['nom' => 'MAVOUNGOU', 'prenom' => 'Clara',   'sexe' => 'F', 'ddn' => '2001-03-22', 'qualite' => 'etudes'],
                ['nom' => 'MAVOUNGOU', 'prenom' => 'Patrick', 'sexe' => 'M', 'ddn' => '2004-09-15', 'qualite' => 'standard'],
                ['nom' => 'MAVOUNGOU', 'prenom' => 'Élisa',   'sexe' => 'F', 'ddn' => '2009-06-30', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MAVOUNGOU', 'prenom' => 'Henri', 'tel' => '+242 06 100 200 30', 'relation' => 'Époux'],
        ],
        'ARFT-00013' => [
            'adresse' => '19 Rue Nkombo', 'quartier' => 'Poto-Poto', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'MAMPOUYA', 'prenom' => 'Fatou', 'sexe' => 'F', 'ddn' => '1982-04-28'],
            'enfants' => [
                ['nom' => 'MAMPOUYA', 'prenom' => 'Junior', 'sexe' => 'M', 'ddn' => '2010-01-15', 'qualite' => 'standard'],
                ['nom' => 'MAMPOUYA', 'prenom' => 'Diana',  'sexe' => 'F', 'ddn' => '2013-08-20', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MAMPOUYA', 'prenom' => 'Fatou', 'tel' => '+242 06 200 300 40', 'relation' => 'Épouse'],
        ],
        'ARFT-00014' => [
            'adresse' => '8 Avenue du Général de Gaulle', 'quartier' => 'Centre-ville', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 1,
            'conjoint' => ['nom' => 'NGOUBILI', 'prenom' => 'Albert', 'sexe' => 'M', 'ddn' => '1981-11-20'],
            'enfants' => [
                ['nom' => 'NGOUBILI', 'prenom' => 'Axel', 'sexe' => 'M', 'ddn' => '2012-07-05', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NGOUBILI', 'prenom' => 'Albert', 'tel' => '+242 06 300 400 50', 'relation' => 'Époux'],
        ],
        'ARFT-00015' => [
            'adresse' => '11 Cité Djiri', 'quartier' => 'Djiri', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'TSIBA', 'prenom' => 'Victoire', 'tel' => '+242 06 400 500 60', 'relation' => 'Mère'],
        ],
        'ARFT-00016' => [
            'adresse' => '33 Rue Makoua', 'quartier' => 'Moungali', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'ITOUA', 'prenom' => 'Bertrand', 'sexe' => 'M', 'ddn' => '1987-09-14'],
            'enfants' => [
                ['nom' => 'ITOUA', 'prenom' => 'Lukas', 'sexe' => 'M', 'ddn' => '2015-12-18', 'qualite' => 'standard'],
                ['nom' => 'ITOUA', 'prenom' => 'Emma',  'sexe' => 'F', 'ddn' => '2018-03-07', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'ITOUA', 'prenom' => 'Bertrand', 'tel' => '+242 06 500 600 70', 'relation' => 'Époux'],
        ],
        // ── A.C ─────────────────────────────────────────────────────────
        'ARFT-00022' => [
            'adresse' => '17 Boulevard du 15 Août', 'quartier' => 'Centre-ville', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'BAHAMBOULA', 'prenom' => 'Ernest', 'sexe' => 'M', 'ddn' => '1967-08-31'],
            'enfants' => [
                ['nom' => 'BAHAMBOULA', 'prenom' => 'Chloé',  'sexe' => 'F', 'ddn' => '1998-05-16', 'qualite' => 'etudes'],
                ['nom' => 'BAHAMBOULA', 'prenom' => 'Maxime', 'sexe' => 'M', 'ddn' => '2001-11-09', 'qualite' => 'etudes'],
                ['nom' => 'BAHAMBOULA', 'prenom' => 'Odile',  'sexe' => 'F', 'ddn' => '2005-07-23', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'BAHAMBOULA', 'prenom' => 'Ernest', 'tel' => '+242 06 600 700 80', 'relation' => 'Époux'],
        ],
        'ARFT-00023' => [
            'adresse' => '6 Rue des Cocotiers', 'quartier' => 'Ouenzé', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'MANTSANGA', 'prenom' => 'Évelyne', 'sexe' => 'F', 'ddn' => '1978-01-15'],
            'enfants' => [
                ['nom' => 'MANTSANGA', 'prenom' => 'Arnold', 'sexe' => 'M', 'ddn' => '2007-09-28', 'qualite' => 'standard'],
                ['nom' => 'MANTSANGA', 'prenom' => 'Flore',  'sexe' => 'F', 'ddn' => '2010-04-11', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MANTSANGA', 'prenom' => 'Évelyne', 'tel' => '+242 06 700 800 90', 'relation' => 'Épouse'],
        ],
        'ARFT-00024' => [
            'adresse' => '20 Rue Victor Hugo', 'quartier' => 'Bacongo', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'MOUSSOKI', 'prenom' => 'Thomas', 'tel' => '+242 05 100 200 30', 'relation' => 'Frère'],
        ],
        'ARFT-00025' => [
            'adresse' => '44 Avenue de l\'Armée', 'quartier' => 'Poto-Poto', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 1,
            'enfants' => [
                ['nom' => 'MAFOUTA', 'prenom' => 'Ruben', 'sexe' => 'M', 'ddn' => '2017-10-03', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MAFOUTA', 'prenom' => 'Delphine', 'tel' => '+242 05 200 300 40', 'relation' => 'Mère'],
        ],
        'ARFT-00026' => [
            'adresse' => '15 Cité Siafoumou', 'quartier' => 'Talangaï', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'DZABATOU', 'prenom' => 'Roland', 'sexe' => 'M', 'ddn' => '1989-06-22'],
            'enfants' => [
                ['nom' => 'DZABATOU', 'prenom' => 'Noa',  'sexe' => 'M', 'ddn' => '2016-02-14', 'qualite' => 'standard'],
                ['nom' => 'DZABATOU', 'prenom' => 'Zara', 'sexe' => 'F', 'ddn' => '2019-09-01', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'DZABATOU', 'prenom' => 'Roland', 'tel' => '+242 05 300 400 50', 'relation' => 'Époux'],
        ],
        // ── D.I.S.E ──────────────────────────────────────────────────────
        'ARFT-00027' => [
            'adresse' => '3 Rue Gamboma', 'quartier' => 'Moungali', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'LOUBOTA', 'prenom' => 'Serge', 'sexe' => 'M', 'ddn' => '1972-06-17'],
            'enfants' => [
                ['nom' => 'LOUBOTA', 'prenom' => 'Alicia', 'sexe' => 'F', 'ddn' => '2002-08-25', 'qualite' => 'etudes'],
                ['nom' => 'LOUBOTA', 'prenom' => 'Théodore','sexe' => 'M', 'ddn' => '2006-12-11', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'LOUBOTA', 'prenom' => 'Serge', 'tel' => '+242 05 400 500 60', 'relation' => 'Époux'],
        ],
        'ARFT-00028' => [
            'adresse' => '22 Impasse des Manguiers', 'quartier' => 'Ouenzé', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'OSSOKO', 'prenom' => 'Bernadette', 'sexe' => 'F', 'ddn' => '1983-03-19'],
            'enfants' => [
                ['nom' => 'OSSOKO', 'prenom' => 'Lionel', 'sexe' => 'M', 'ddn' => '2008-06-07', 'qualite' => 'standard'],
                ['nom' => 'OSSOKO', 'prenom' => 'Mélanie','sexe' => 'F', 'ddn' => '2011-10-24', 'qualite' => 'standard'],
                ['nom' => 'OSSOKO', 'prenom' => 'Raphaël','sexe' => 'M', 'ddn' => '2014-04-02', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'OSSOKO', 'prenom' => 'Bernadette', 'tel' => '+242 05 500 600 70', 'relation' => 'Épouse'],
        ],
        'ARFT-00029' => [
            'adresse' => '9 Rue du Port', 'quartier' => 'Poto-Poto', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 1,
            'conjoint' => ['nom' => 'OTIOBANDA', 'prenom' => 'Claude', 'sexe' => 'M', 'ddn' => '1984-07-08'],
            'enfants' => [
                ['nom' => 'OTIOBANDA', 'prenom' => 'Léa', 'sexe' => 'F', 'ddn' => '2014-11-30', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'OTIOBANDA', 'prenom' => 'Claude', 'tel' => '+242 05 600 700 80', 'relation' => 'Époux'],
        ],
        'ARFT-00030' => [
            'adresse' => '5 Avenue de la Tsiémé', 'quartier' => 'Djiri', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'MAYINDOU', 'prenom' => 'Félicité', 'tel' => '+242 05 700 800 90', 'relation' => 'Mère'],
        ],
        'ARFT-00031' => [
            'adresse' => '28 Rue Brazzaville', 'quartier' => 'Makélékélé', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 1,
            'enfants' => [
                ['nom' => 'NGATSONO', 'prenom' => 'Samuel', 'sexe' => 'M', 'ddn' => '2019-05-16', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NGATSONO', 'prenom' => 'Marie', 'tel' => '+242 05 800 900 01', 'relation' => 'Mère'],
        ],
        // ── D.A.J.I.C ────────────────────────────────────────────────────
        'ARFT-00032' => [
            'adresse' => '7 Avenue du Palais', 'quartier' => 'Centre-ville', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'BITSINDOU', 'prenom' => 'Patrick', 'sexe' => 'M', 'ddn' => '1973-05-04'],
            'enfants' => [
                ['nom' => 'BITSINDOU', 'prenom' => 'Jade',    'sexe' => 'F', 'ddn' => '2001-09-14', 'qualite' => 'etudes'],
                ['nom' => 'BITSINDOU', 'prenom' => 'Vincent', 'sexe' => 'M', 'ddn' => '2004-02-27', 'qualite' => 'standard'],
                ['nom' => 'BITSINDOU', 'prenom' => 'Ines',    'sexe' => 'F', 'ddn' => '2008-11-03', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'BITSINDOU', 'prenom' => 'Patrick', 'tel' => '+242 04 100 200 30', 'relation' => 'Époux'],
        ],
        'ARFT-00033' => [
            'adresse' => '34 Rue du Commissariat', 'quartier' => 'Bacongo', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'NGANDZALI', 'prenom' => 'Rose', 'tel' => '+242 04 200 300 40', 'relation' => 'Mère'],
        ],
        'ARFT-00034' => [
            'adresse' => '11 Cité Nkombo', 'quartier' => 'Ouenzé', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'BOUDZOUMOU', 'prenom' => 'Serge', 'sexe' => 'M', 'ddn' => '1985-04-12'],
            'enfants' => [
                ['nom' => 'BOUDZOUMOU', 'prenom' => 'Élodie', 'sexe' => 'F', 'ddn' => '2015-07-09', 'qualite' => 'standard'],
                ['nom' => 'BOUDZOUMOU', 'prenom' => 'Damien', 'sexe' => 'M', 'ddn' => '2018-01-24', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'BOUDZOUMOU', 'prenom' => 'Serge', 'tel' => '+242 04 300 400 50', 'relation' => 'Époux'],
        ],
        'ARFT-00035' => [
            'adresse' => '25 Avenue de la CNSS', 'quartier' => 'Poto-Poto', 'ville' => 'Brazzaville',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'MADZOU', 'prenom' => 'Inès', 'tel' => '+242 04 400 500 60', 'relation' => 'Sœur'],
        ],
        'ARFT-00036' => [
            'adresse' => '13 Rue des Acacia', 'quartier' => 'Talangaï', 'ville' => 'Brazzaville',
            'statut' => 'marie', 'nb_enfants' => 1,
            'conjoint' => ['nom' => 'KIMFOKO', 'prenom' => 'Bruno', 'sexe' => 'M', 'ddn' => '1991-08-03'],
            'enfants' => [
                ['nom' => 'KIMFOKO', 'prenom' => 'Hanna', 'sexe' => 'F', 'ddn' => '2018-06-12', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'KIMFOKO', 'prenom' => 'Bruno', 'tel' => '+242 04 500 600 70', 'relation' => 'Époux'],
        ],
        // ── D.D.P.N ─────────────────────────────────────────────────────
        'ARFT-00037' => [
            'adresse' => '1 Rue du Port', 'quartier' => 'Loandjili', 'ville' => 'Pointe-Noire',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'MOUKENGUE', 'prenom' => 'Félix', 'sexe' => 'M', 'ddn' => '1974-10-25'],
            'enfants' => [
                ['nom' => 'MOUKENGUE', 'prenom' => 'Éric',  'sexe' => 'M', 'ddn' => '2003-03-19', 'qualite' => 'standard'],
                ['nom' => 'MOUKENGUE', 'prenom' => 'Laure', 'sexe' => 'F', 'ddn' => '2007-08-06', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MOUKENGUE', 'prenom' => 'Félix', 'tel' => '+242 05 100 111 22', 'relation' => 'Époux'],
        ],
        'ARFT-00038' => [
            'adresse' => '22 Cité SIAFOUMOU PN', 'quartier' => 'Tie-Tie', 'ville' => 'Pointe-Noire',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'MFOUBOU', 'prenom' => 'Pauline', 'sexe' => 'F', 'ddn' => '1986-05-30'],
            'enfants' => [
                ['nom' => 'MFOUBOU', 'prenom' => 'Calvin',  'sexe' => 'M', 'ddn' => '2010-07-22', 'qualite' => 'standard'],
                ['nom' => 'MFOUBOU', 'prenom' => 'Doriane', 'sexe' => 'F', 'ddn' => '2013-02-14', 'qualite' => 'standard'],
                ['nom' => 'MFOUBOU', 'prenom' => 'Marcus',  'sexe' => 'M', 'ddn' => '2016-09-05', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MFOUBOU', 'prenom' => 'Pauline', 'tel' => '+242 05 200 222 33', 'relation' => 'Épouse'],
        ],
        'ARFT-00039' => [
            'adresse' => '8 Rue Ange Mabika', 'quartier' => 'Fouks', 'ville' => 'Pointe-Noire',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'LOUZOLO', 'prenom' => 'César', 'sexe' => 'M', 'ddn' => '1987-11-18'],
            'enfants' => [
                ['nom' => 'LOUZOLO', 'prenom' => 'Riva', 'sexe' => 'F', 'ddn' => '2015-04-01', 'qualite' => 'standard'],
                ['nom' => 'LOUZOLO', 'prenom' => 'Axel', 'sexe' => 'M', 'ddn' => '2018-10-09', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'LOUZOLO', 'prenom' => 'César', 'tel' => '+242 05 300 333 44', 'relation' => 'Époux'],
        ],
        'ARFT-00040' => [
            'adresse' => '15 Avenue Nguyen Van Troi PN', 'quartier' => 'Loandjili', 'ville' => 'Pointe-Noire',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'NGAKOSSO', 'prenom' => 'Célestine', 'tel' => '+242 05 400 444 55', 'relation' => 'Mère'],
        ],
        'ARFT-00041' => [
            'adresse' => '3 Cité Mossendjo PN', 'quartier' => 'Mvou-Mvou', 'ville' => 'Pointe-Noire',
            'statut' => 'marie', 'nb_enfants' => 1,
            'conjoint' => ['nom' => 'BABINDAMANA', 'prenom' => 'Alain', 'sexe' => 'M', 'ddn' => '1991-07-14'],
            'enfants' => [
                ['nom' => 'BABINDAMANA', 'prenom' => 'Joy', 'sexe' => 'F', 'ddn' => '2019-03-28', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'BABINDAMANA', 'prenom' => 'Alain', 'tel' => '+242 05 500 555 66', 'relation' => 'Époux'],
        ],
        // ── D.D.O ─────────────────────────────────────────────────────────
        'ARFT-00042' => [
            'adresse' => '4 Rue de la Rivière Sangha', 'quartier' => 'Centre Ouesso', 'ville' => 'Ouesso',
            'statut' => 'marie', 'nb_enfants' => 4,
            'conjoint' => ['nom' => 'MAKAYA', 'prenom' => 'Christine', 'sexe' => 'F', 'ddn' => '1979-03-12'],
            'enfants' => [
                ['nom' => 'MAKAYA', 'prenom' => 'Frédéric',  'sexe' => 'M', 'ddn' => '2002-06-28', 'qualite' => 'etudes'],
                ['nom' => 'MAKAYA', 'prenom' => 'Stéphanie', 'sexe' => 'F', 'ddn' => '2005-11-14', 'qualite' => 'standard'],
                ['nom' => 'MAKAYA', 'prenom' => 'Arnaud',    'sexe' => 'M', 'ddn' => '2009-03-07', 'qualite' => 'standard'],
                ['nom' => 'MAKAYA', 'prenom' => 'Céleste',   'sexe' => 'F', 'ddn' => '2013-08-23', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MAKAYA', 'prenom' => 'Christine', 'tel' => '+242 04 100 111 22', 'relation' => 'Épouse'],
        ],
        'ARFT-00043' => [
            'adresse' => '10 Rue des Cascades', 'quartier' => 'Centre Ouesso', 'ville' => 'Ouesso',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'NGOUMA', 'prenom' => 'Martin', 'sexe' => 'M', 'ddn' => '1983-07-02'],
            'enfants' => [
                ['nom' => 'NGOUMA', 'prenom' => 'Eva',  'sexe' => 'F', 'ddn' => '2011-04-18', 'qualite' => 'standard'],
                ['nom' => 'NGOUMA', 'prenom' => 'Noé',  'sexe' => 'M', 'ddn' => '2014-11-30', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'NGOUMA', 'prenom' => 'Martin', 'tel' => '+242 04 200 222 33', 'relation' => 'Époux'],
        ],
        'ARFT-00044' => [
            'adresse' => '7 Cité Administrative Ouesso', 'quartier' => 'Centre Ouesso', 'ville' => 'Ouesso',
            'statut' => 'celibataire', 'nb_enfants' => 1,
            'enfants' => [
                ['nom' => 'TATY', 'prenom' => 'Célia', 'sexe' => 'F', 'ddn' => '2016-09-14', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'TATY', 'prenom' => 'Alice', 'tel' => '+242 04 300 333 44', 'relation' => 'Mère'],
        ],
        'ARFT-00045' => [
            'adresse' => '19 Rue de la Forêt', 'quartier' => 'Centre Ouesso', 'ville' => 'Ouesso',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'FOUATOU', 'prenom' => 'Denise', 'tel' => '+242 04 400 444 55', 'relation' => 'Mère'],
        ],
        'ARFT-00046' => [
            'adresse' => '2 Rue des Piroguiers', 'quartier' => 'Centre Ouesso', 'ville' => 'Ouesso',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'LOUBASSOU', 'prenom' => 'Thérèse', 'sexe' => 'F', 'ddn' => '1997-12-05'],
            'enfants' => [
                ['nom' => 'LOUBASSOU', 'prenom' => 'Mao',  'sexe' => 'M', 'ddn' => '2020-03-15', 'qualite' => 'standard'],
                ['nom' => 'LOUBASSOU', 'prenom' => 'Lily', 'sexe' => 'F', 'ddn' => '2022-08-22', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'LOUBASSOU', 'prenom' => 'Thérèse', 'tel' => '+242 04 500 555 66', 'relation' => 'Épouse'],
        ],
        // ── D.D.D ─────────────────────────────────────────────────────────
        'ARFT-00047' => [
            'adresse' => '1 Avenue du Gouverneur', 'quartier' => 'Centre Dolisie', 'ville' => 'Dolisie',
            'statut' => 'marie', 'nb_enfants' => 3,
            'conjoint' => ['nom' => 'DIAKABANA', 'prenom' => 'Yvonne', 'sexe' => 'F', 'ddn' => '1979-04-06'],
            'enfants' => [
                ['nom' => 'DIAKABANA', 'prenom' => 'Jordan',    'sexe' => 'M', 'ddn' => '2003-01-21', 'qualite' => 'etudes'],
                ['nom' => 'DIAKABANA', 'prenom' => 'Sandrine',  'sexe' => 'F', 'ddn' => '2006-07-13', 'qualite' => 'standard'],
                ['nom' => 'DIAKABANA', 'prenom' => 'Tristan',   'sexe' => 'M', 'ddn' => '2010-11-29', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'DIAKABANA', 'prenom' => 'Yvonne', 'tel' => '+242 04 600 666 77', 'relation' => 'Épouse'],
        ],
        'ARFT-00048' => [
            'adresse' => '16 Rue du Marché Dolisie', 'quartier' => 'Centre Dolisie', 'ville' => 'Dolisie',
            'statut' => 'marie', 'nb_enfants' => 2,
            'conjoint' => ['nom' => 'BATAMBILA', 'prenom' => 'Emmanuel', 'sexe' => 'M', 'ddn' => '1979-10-08'],
            'enfants' => [
                ['nom' => 'BATAMBILA', 'prenom' => 'Iris',  'sexe' => 'F', 'ddn' => '2009-06-03', 'qualite' => 'standard'],
                ['nom' => 'BATAMBILA', 'prenom' => 'Kévin', 'sexe' => 'M', 'ddn' => '2012-12-17', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'BATAMBILA', 'prenom' => 'Emmanuel', 'tel' => '+242 04 700 777 88', 'relation' => 'Époux'],
        ],
        'ARFT-00049' => [
            'adresse' => '5 Cité Dolisie', 'quartier' => 'Mbanda', 'ville' => 'Dolisie',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'MASSAMBA', 'prenom' => 'Geneviève', 'tel' => '+242 04 800 888 99', 'relation' => 'Mère'],
        ],
        'ARFT-00050' => [
            'adresse' => '9 Rue des Fleurs Dolisie', 'quartier' => 'Centre Dolisie', 'ville' => 'Dolisie',
            'statut' => 'celibataire', 'nb_enfants' => 1,
            'enfants' => [
                ['nom' => 'MANKESSI', 'prenom' => 'Paul', 'sexe' => 'M', 'ddn' => '2017-02-11', 'qualite' => 'standard'],
            ],
            'contact_urgence' => ['nom' => 'MANKESSI', 'prenom' => 'Rosette', 'tel' => '+242 04 900 999 00', 'relation' => 'Mère'],
        ],
        'ARFT-00051' => [
            'adresse' => '27 Avenue du Stadium Dolisie', 'quartier' => 'Mbanda', 'ville' => 'Dolisie',
            'statut' => 'celibataire', 'nb_enfants' => 0,
            'contact_urgence' => ['nom' => 'NGANGA', 'prenom' => 'Suzanne', 'tel' => '+242 04 111 222 33', 'relation' => 'Mère'],
        ],
    ];

    public function run(): void
    {
        $ip = 0; $sf = 0; $ad = 0; $cu = 0;

        foreach (self::PERSONAS as $matricule => $data) {
            $agent = Agent::where('matricule', $matricule)->first();
            if (! $agent) continue;

            // ── InformationsPersonnelles ──────────────────────────────
            InformationsPersonnelle::firstOrCreate(
                ['agent_id' => $agent->id],
                [
                    'adresse'    => $data['adresse'],
                    'quartier'   => $data['quartier'],
                    'ville'      => $data['ville'],
                    'pays'       => 'Congo',
                ]
            ) && $ip++;

            // ── SituationFamiliale ────────────────────────────────────
            SituationFamiliale::firstOrCreate(
                ['agent_id' => $agent->id],
                [
                    'statut_matrimonial' => $data['statut'],
                    'nb_enfants'         => $data['nb_enfants'],
                ]
            ) && $sf++;

            // ── AyantsDroits — Conjoint ───────────────────────────────
            if (! empty($data['conjoint'])) {
                $c = $data['conjoint'];
                if (! AyantDroit::where('agent_id', $agent->id)->where('type', TypeAyantDroit::CONJOINT)->exists()) {
                    AyantDroit::create([
                        'agent_id'        => $agent->id,
                        'type'            => TypeAyantDroit::CONJOINT,
                        'nom'             => $c['nom'],
                        'prenom'          => $c['prenom'],
                        'date_naissance'  => $c['ddn'],
                        'sexe'            => $c['sexe'],
                        'lien_juridique'  => LienJuridiqueAyantDroit::MARIAGE,
                        'date_debut'      => $agent->date_prise_service,
                        'actif'           => true,
                    ]);
                    $ad++;
                }
            }

            // ── AyantsDroits — Enfants ────────────────────────────────
            if (! empty($data['enfants'])) {
                $existants = AyantDroit::where('agent_id', $agent->id)
                    ->where('type', TypeAyantDroit::ENFANT)->count();
                if ($existants === 0) {
                    foreach ($data['enfants'] as $enf) {
                        AyantDroit::create([
                            'agent_id'       => $agent->id,
                            'type'           => TypeAyantDroit::ENFANT,
                            'nom'            => $enf['nom'],
                            'prenom'         => $enf['prenom'],
                            'date_naissance' => $enf['ddn'],
                            'sexe'           => $enf['sexe'],
                            'lien_juridique' => LienJuridiqueAyantDroit::MARIAGE,
                            'qualite_age'    => QualiteAgeAyantDroit::from($enf['qualite']),
                            'date_debut'     => $agent->date_prise_service,
                            'actif'          => true,
                        ]);
                        $ad++;
                    }
                }
            }

            // ── ContactUrgence ────────────────────────────────────────
            if (! empty($data['contact_urgence'])) {
                $cu_data = $data['contact_urgence'];
                if (! ContactUrgence::where('agent_id', $agent->id)->exists()) {
                    ContactUrgence::create([
                        'agent_id'  => $agent->id,
                        'nom'       => $cu_data['nom'],
                        'prenom'    => $cu_data['prenom'],
                        'telephone' => $cu_data['tel'],
                        'relation'  => $cu_data['relation'],
                    ]);
                    $cu++;
                }
            }
        }

        $this->command?->info("Infos perso : {$ip} — Situations familiales : {$sf} — Ayants droit : {$ad} — Contacts urgence : {$cu}.");
    }
}
