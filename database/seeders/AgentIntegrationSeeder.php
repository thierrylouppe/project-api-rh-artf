<?php

namespace Database\Seeders;

use App\Enums\StatutAffectation;
use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Contrat;
use App\Models\Direction;
use App\Models\Nomination;
use App\Models\Service;
use App\Models\Categorie;
use App\Models\Echelon;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\TypeContrat;
use App\Models\TypeIntegration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AgentIntegrationSeeder — Personas fixes
 *
 * 41 agents répartis sur les 10 directions :
 *   ▸ 1 Directeur Général
 *   ▸ Par direction : Directeur · Chef de service · Chef de bureau · 2 Agents
 *
 * Les directions sans service/bureau (D.D.P.N, D.D.O, D.D.D, D.A.J.I.C) reçoivent
 * des structures administratives créées à la volée.
 *
 * Chaque agent permanent obtient un compte User avec le rôle associé à sa fonction.
 * Les stagiaires n'ont pas de compte (CCN art. 46 — necessite_compte_utilisateur = false).
 *
 * ─── Convention des identifiants ────────────────────────────────────────────
 *  Email    : prenom.nom@arft.cg  (sans accents, minuscules)
 *  Password : Nom@2026            (ex: Moukala@2026)
 */
class AgentIntegrationSeeder extends Seeder
{
    // ── Mappage rôle DRHL par bureau ─────────────────────────────────────
    private const ROLES_DRHL = [
        'B.P'    => 'rh-personnel',
        'B.F'    => 'rh-formation',
        'B.S.'   => 'rh-solde',
        'B.A.S.' => 'rh-affaires-sociales',
        'B.PL'   => 'rh-etude',
    ];

    // ─────────────────────────────────────────────────────────────────────
    // Données fixes — 10 directions
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Chaque entrée décrit une direction complète.
     *
     * Clés :
     *  direction      → sigle de la direction
     *  service_sigle  → sigle du service rattaché (créé si manquant)
     *  service_nom    → nom complet du service (utilisé à la création)
     *  bureau_sigle   → sigle du bureau (créé si manquant)
     *  bureau_nom     → nom complet du bureau
     *  directeur      → persona du directeur
     *  chef_service   → persona du chef de service
     *  chef_bureau    → persona du chef de bureau
     *  agents         → [ persona, persona ]
     *  stagiaire      → persona (sans compte User)
     */
    private function hierarchies(): array
    {
        $debutStage = Carbon::now()->subMonths(3)->toDateString();
        $finStage   = Carbon::now()->addMonths(3)->toDateString();

        return [

            // ══════════════════════════════════════════════════════════════
            // 1. Direction Générale
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.G',
                'service_sigle' => 'S.S.I',
                'bureau_sigle'  => 'B.EXP',

                'directeur' => [
                    'matricule' => 'ARFT-00002',
                    'nom' => 'NZABA',        'prenom' => 'Marie-Rose',
                    'genre' => 'F',          'ddn' => '1972-07-12',
                    'poste' => 'DIRECTION GENERALE',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00003',
                    'nom' => 'NGOMA',        'prenom' => 'Emmanuel',
                    'genre' => 'M',          'ddn' => '1978-02-28',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00004',
                    'nom' => 'MBEMBA',       'prenom' => 'Parfait',
                    'genre' => 'M',          'ddn' => '1982-11-05',
                    'dps' => '2017-06-01',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00005', 'nom' => 'LOEMBA',   'prenom' => 'Solange',  'genre' => 'F', 'ddn' => '1990-04-18', 'dps' => '2020-01-06'],
                    ['matricule' => 'ARFT-00006', 'nom' => 'KIBANGOU', 'prenom' => 'Fernand',  'genre' => 'M', 'ddn' => '1988-09-22', 'dps' => '2020-01-06'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0001', 'nom' => 'MALONGA', 'prenom' => 'Christelle',
                    'genre' => 'F', 'ddn' => '2002-06-14', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 2. Direction Financière
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.F',
                'service_sigle' => 'S.B',
                'bureau_sigle'  => 'B.RCT',

                'directeur' => [
                    'matricule' => 'ARFT-00007',
                    'nom' => 'BIYOUDI',      'prenom' => 'Pierre',
                    'genre' => 'M',          'ddn' => '1970-09-08',
                    'poste' => 'DIRECTION FINANCIERE',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00008',
                    'nom' => 'ONDONGO',      'prenom' => 'Aimée',
                    'genre' => 'F',          'ddn' => '1976-03-15',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00009',
                    'nom' => 'NSIMBA',       'prenom' => 'Cédric',
                    'genre' => 'M',          'ddn' => '1983-07-20',
                    'dps' => '2017-06-01',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00010', 'nom' => 'MOUAMBA', 'prenom' => 'Nadège',   'genre' => 'F', 'ddn' => '1991-01-30', 'dps' => '2020-03-02'],
                    ['matricule' => 'ARFT-00011', 'nom' => 'NKAYA',   'prenom' => 'Rodrigue', 'genre' => 'M', 'ddn' => '1987-12-05', 'dps' => '2020-03-02'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0002', 'nom' => 'BANZOUZI', 'prenom' => 'Sylvestre',
                    'genre' => 'M', 'ddn' => '2003-04-22', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 3. Direction de la Régulation
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.R',
                'service_sigle' => 'S.A.R',
                'bureau_sigle'  => 'B.O.M.M.A',

                'directeur' => [
                    'matricule' => 'ARFT-00012',
                    'nom' => 'MAVOUNGOU',    'prenom' => 'Alvine',
                    'genre' => 'F',          'ddn' => '1973-05-18',
                    'poste' => 'DIRECTION DE LA REGULATION',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00013',
                    'nom' => 'MAMPOUYA',     'prenom' => 'Rémy',
                    'genre' => 'M',          'ddn' => '1979-08-10',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00014',
                    'nom' => 'NGOUBILI',     'prenom' => 'Carmélie',
                    'genre' => 'F',          'ddn' => '1984-02-25',
                    'dps' => '2017-06-01',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00015', 'nom' => 'TSIBA',  'prenom' => 'Bertrand', 'genre' => 'M', 'ddn' => '1992-07-14', 'dps' => '2020-04-01'],
                    ['matricule' => 'ARFT-00016', 'nom' => 'ITOUA',  'prenom' => 'Déborah',  'genre' => 'F', 'ddn' => '1989-11-03', 'dps' => '2020-04-01'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0003', 'nom' => 'POATY', 'prenom' => 'Serge',
                    'genre' => 'M', 'ddn' => '2001-09-17', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 4. Direction des Ressources Humaines et de la Logistique
            //    → Bureau Formation (B.F) → rôle rh-formation
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.R.H.L',
                'service_sigle' => 'S.R.H',
                'bureau_sigle'  => 'B.F',

                'directeur' => [
                    'matricule' => 'ARFT-00017',
                    'nom' => 'GAMBOU',       'prenom' => 'Lydiane',
                    'genre' => 'F',          'ddn' => '1971-12-02',
                    'poste' => 'DIRECTION DES RESSOURCES HUMAINES ET DE LA LOGISTIQUE',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00018',
                    'nom' => 'MILANDOU',     'prenom' => 'Hervé',
                    'genre' => 'M',          'ddn' => '1977-06-25',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00019',
                    'nom' => 'MOUNANGA',     'prenom' => 'Rosette',
                    'genre' => 'F',          'ddn' => '1985-03-11',
                    'dps' => '2017-06-01',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00020', 'nom' => 'LOUBAKI',   'prenom' => 'Yves',     'genre' => 'M', 'ddn' => '1993-08-28', 'dps' => '2020-05-04'],
                    ['matricule' => 'ARFT-00021', 'nom' => 'NKOUNKOU',  'prenom' => 'Gilberte', 'genre' => 'F', 'ddn' => '1990-02-06', 'dps' => '2020-05-04'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0004', 'nom' => 'BIKOUTA', 'prenom' => 'Fulgence',
                    'genre' => 'M', 'ddn' => '2002-11-19', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 5. Agence Comptable
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'A.C',
                'service_sigle' => 'S.D',
                'bureau_sigle'  => 'B.REC',

                'directeur' => [
                    'matricule' => 'ARFT-00022',
                    'nom' => 'BAHAMBOULA',   'prenom' => 'Sylvie',
                    'genre' => 'F',          'ddn' => '1969-04-30',
                    'poste' => 'AGENCE COMPTABLE',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00023',
                    'nom' => 'MANTSANGA',    'prenom' => 'Hyacinthe',
                    'genre' => 'M',          'ddn' => '1975-10-17',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00024',
                    'nom' => 'MOUSSOKI',     'prenom' => 'Marcelline',
                    'genre' => 'F',          'ddn' => '1981-06-08',
                    'dps' => '2017-06-01',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00025', 'nom' => 'MAFOUTA',   'prenom' => 'Jocelyn', 'genre' => 'M', 'ddn' => '1994-03-23', 'dps' => '2020-06-01'],
                    ['matricule' => 'ARFT-00026', 'nom' => 'DZABATOU',  'prenom' => 'Carine',  'genre' => 'F', 'ddn' => '1991-09-14', 'dps' => '2020-06-01'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0005', 'nom' => 'BOUITI', 'prenom' => 'Théodore',
                    'genre' => 'M', 'ddn' => '2003-07-01', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 6. Direction de l'Inspection des Statistiques et des Études
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.I.S.E',
                'service_sigle' => 'S.E',
                'bureau_sigle'  => 'B.E.G',

                'directeur' => [
                    'matricule' => 'ARFT-00027',
                    'nom' => 'LOUBOTA',      'prenom' => 'Michèle',
                    'genre' => 'F',          'ddn' => '1974-01-27',
                    'poste' => "DIRECTION DE L'INSPECTION DES STAT. ET DES ETUDES",
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00028',
                    'nom' => 'OSSOKO',       'prenom' => 'Dieudonné',
                    'genre' => 'M',          'ddn' => '1980-05-14',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00029',
                    'nom' => 'OTIOBANDA',    'prenom' => 'Irène',
                    'genre' => 'F',          'ddn' => '1986-10-09',
                    'dps' => '2017-06-01',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00030', 'nom' => 'MAYINDOU',  'prenom' => 'Lionel',    'genre' => 'M', 'ddn' => '1995-04-16', 'dps' => '2021-01-04'],
                    ['matricule' => 'ARFT-00031', 'nom' => 'NGATSONO',  'prenom' => 'Bénédicte', 'genre' => 'F', 'ddn' => '1992-12-31', 'dps' => '2021-01-04'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0006', 'nom' => 'KIMBOUALA', 'prenom' => 'Ariel',
                    'genre' => 'M', 'ddn' => '2004-02-28', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 7. Direction des Affaires Juridiques, Investissements et Coop.
            //    → Service + bureau créés à la volée (aucun bureau existant)
            // ══════════════════════════════════════════════════════════════
            [
                'direction'      => 'D.A.J.I.C',
                'service_sigle'  => 'S.A.J',
                'bureau_sigle'   => 'B.J',
                'bureau_nom'     => 'BUREAU JURIDIQUE',
                // service_nom absent → le service S.A.J existe déjà

                'directeur' => [
                    'matricule' => 'ARFT-00032',
                    'nom' => 'BITSINDOU',    'prenom' => 'Florence',
                    'genre' => 'F',          'ddn' => '1975-08-23',
                    'poste' => 'DIRECTION DES AFF. JURIDIQUES, DES INVEST. ET DE LA COOP.',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00033',
                    'nom' => 'NGANDZALI',    'prenom' => 'Wilfried',
                    'genre' => 'M',          'ddn' => '1981-03-07',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00034',
                    'nom' => 'BOUDZOUMOU',   'prenom' => 'Nadège',
                    'genre' => 'F',          'ddn' => '1987-06-15',
                    'dps' => '2018-01-02',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00035', 'nom' => 'MADZOU',    'prenom' => 'Stève',    'genre' => 'M', 'ddn' => '1994-10-11', 'dps' => '2021-03-01'],
                    ['matricule' => 'ARFT-00036', 'nom' => 'KIMFOKO',   'prenom' => 'Agnès',    'genre' => 'F', 'ddn' => '1993-05-22', 'dps' => '2021-03-01'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0007', 'nom' => 'NZINGA', 'prenom' => 'Calixte',
                    'genre' => 'M', 'ddn' => '2005-01-09', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 8. Direction Départementale Pointe-Noire
            //    → Service + bureau créés à la volée
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.D.P.N',
                'service_sigle' => 'S.ADM.PN',
                'service_nom'   => 'SERVICE ADMINISTRATIF POINTE-NOIRE',
                'bureau_sigle'  => 'B.ADM.PN',
                'bureau_nom'    => 'BUREAU ADMINISTRATIF POINTE-NOIRE',

                'directeur' => [
                    'matricule' => 'ARFT-00037',
                    'nom' => 'MOUKENGUE',    'prenom' => 'Victoire',
                    'genre' => 'F',          'ddn' => '1976-02-11',
                    'poste' => 'DIRECTION DEPARTEMENTALE POINTE-NOIRE',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00038',
                    'nom' => 'MFOUBOU',      'prenom' => 'Clovis',
                    'genre' => 'M',          'ddn' => '1983-09-19',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00039',
                    'nom' => 'LOUZOLO',      'prenom' => 'Joséphine',
                    'genre' => 'F',          'ddn' => '1989-04-27',
                    'dps' => '2018-01-02',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00040', 'nom' => 'NGAKOSSO',  'prenom' => 'Roch',     'genre' => 'M', 'ddn' => '1996-07-08', 'dps' => '2021-06-01'],
                    ['matricule' => 'ARFT-00041', 'nom' => 'BABINDAMANA','prenom' => 'Viviane',  'genre' => 'F', 'ddn' => '1993-11-25', 'dps' => '2021-06-01'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0008', 'nom' => 'MBILO', 'prenom' => 'Dieu-Merci',
                    'genre' => 'M', 'ddn' => '2004-08-13', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 9. Direction Départementale Ouesso
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.D.O',
                'service_sigle' => 'S.ADM.O',
                'service_nom'   => 'SERVICE ADMINISTRATIF OUESSO',
                'bureau_sigle'  => 'B.ADM.O',
                'bureau_nom'    => 'BUREAU ADMINISTRATIF OUESSO',

                'directeur' => [
                    'matricule' => 'ARFT-00042',
                    'nom' => 'MAKAYA',       'prenom' => 'Gaston',
                    'genre' => 'M',          'ddn' => '1977-06-29',
                    'poste' => 'DIRECTION DEPARTEMENTALE OUESSO',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00043',
                    'nom' => 'NGOUMA',       'prenom' => 'Arlette',
                    'genre' => 'F',          'ddn' => '1985-01-14',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00044',
                    'nom' => 'TATY',         'prenom' => 'Honoré',
                    'genre' => 'M',          'ddn' => '1990-07-03',
                    'dps' => '2018-01-02',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00045', 'nom' => 'FOUATOU',   'prenom' => 'Espérance', 'genre' => 'F', 'ddn' => '1997-03-21', 'dps' => '2022-01-03'],
                    ['matricule' => 'ARFT-00046', 'nom' => 'LOUBASSOU', 'prenom' => 'Noël',      'genre' => 'M', 'ddn' => '1995-12-25', 'dps' => '2022-01-03'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0009', 'nom' => 'MBOSSO', 'prenom' => 'Flore',
                    'genre' => 'F', 'ddn' => '2005-05-17', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],

            // ══════════════════════════════════════════════════════════════
            // 10. Direction Départementale Dolisie
            // ══════════════════════════════════════════════════════════════
            [
                'direction'     => 'D.D.D',
                'service_sigle' => 'S.ADM.D',
                'service_nom'   => 'SERVICE ADMINISTRATIF DOLISIE',
                'bureau_sigle'  => 'B.ADM.D',
                'bureau_nom'    => 'BUREAU ADMINISTRATIF DOLISIE',

                'directeur' => [
                    'matricule' => 'ARFT-00047',
                    'nom' => 'DIAKABANA',    'prenom' => 'Cédric',
                    'genre' => 'M',          'ddn' => '1980-11-14',
                    'poste' => 'DIRECTION DEPARTEMENTALE DOLISIE',
                    'dps' => '2015-03-01',
                ],
                'chef_service' => [
                    'matricule' => 'ARFT-00048',
                    'nom' => 'BATAMBILA',    'prenom' => 'Jacqueline',
                    'genre' => 'F',          'ddn' => '1986-04-08',
                    'dps' => '2016-09-01',
                ],
                'chef_bureau' => [
                    'matricule' => 'ARFT-00049',
                    'nom' => 'NKEOUA',       'prenom' => 'Ghislain',
                    'genre' => 'M',          'ddn' => '1991-09-22',
                    'dps' => '2018-01-02',
                ],
                'agents' => [
                    ['matricule' => 'ARFT-00050', 'nom' => 'MANKESSI',  'prenom' => 'Célestine', 'genre' => 'F', 'ddn' => '1998-06-30', 'dps' => '2022-03-07'],
                    ['matricule' => 'ARFT-00051', 'nom' => 'NGANGA',    'prenom' => 'Calvin',    'genre' => 'M', 'ddn' => '1996-02-18', 'dps' => '2022-03-07'],
                ],
                'stagiaire' => [
                    'matricule' => 'STG-0010', 'nom' => 'MOUSSELE', 'prenom' => 'Marguerite',
                    'genre' => 'F', 'ddn' => '2006-10-04', 'dps' => $debutStage, 'fin' => $finStage,
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Point d'entrée
    // ─────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        // ── Référentiels ─────────────────────────────────────────────────
        $grades      = Grade::pluck('id', 'sigle');
        $categories  = Categorie::pluck('id', 'sigle');
        $echelons    = Echelon::pluck('id', 'numero');
        $fonctions   = Fonction::pluck('id', 'sigle');
        $tiRec       = TypeIntegration::where('nom', 'Recrutement externe')->value('id');
        $tiStage     = TypeIntegration::where('nom', 'Stage professionnel')->value('id');
        $tcCdi       = TypeContrat::where('sigle', 'CDI')->value('id');
        $tcStage     = TypeContrat::where('sigle', 'STG')->value('id');
        $adminId     = User::where('email', 'admin@arft.cg')->value('id') ?? 1;

        // ── 1. Directeur Général ─────────────────────────────────────────
        $dirDG   = Direction::where('sigle', 'D.G')->firstOrFail();
        $bureauDG = null; // DG rattaché à la direction, pas à un bureau

        $dgAgent = $this->creerAgent(
            matricule: 'ARFT-00001',
            nom:       'MOUKALA',
            prenom:    'Jean-Pierre',
            genre:     'M',
            ddn:       '1965-03-15',
            fonctionId: $fonctions['DG'],
            gradeId:    $grades['HC'],
            catId:      $categories['CL-X'],
            echelonId:  $echelons[12],
            tiId:       $tiRec,
            dps:        '2010-01-15',
            statut:     'actif',
        );
        $this->creerAffectation($dgAgent, Direction::class, $dirDG->id, '2010-01-15', $adminId);
        $this->creerNomination($dgAgent, 'Directeur Général', Direction::class, $dirDG->id, '2010-01-15', $adminId);
        $this->creerContrat($dgAgent, $tcCdi, $fonctions['DG'], '2010-01-15');
        $this->creerUser($dgAgent, 'directeur-general', $bureauDG);

        // ── 2. Hiérarchie par direction ──────────────────────────────────
        foreach ($this->hierarchies() as $h) {
            $direction = Direction::where('sigle', $h['direction'])->first();
            if (! $direction) {
                continue;
            }

            // Résolution (ou création) du service
            $service = $this->resoudreService($direction, $h);

            // Résolution (ou création) du bureau
            $bureau = $this->resoudreBureau($service, $h);

            // ── Bureau DRHL → rôle spécifique ────────────────────────────
            $roleCbAgents = self::ROLES_DRHL[$h['bureau_sigle']] ?? null;
            $isDepartementale = str_starts_with($h['direction'], 'D.D');
            $sigleDirecteur   = $isDepartementale ? 'DD' : 'DC';

            // ── Directeur ────────────────────────────────────────────────
            $d = $h['directeur'];
            $directeur = $this->creerAgent(
                matricule: $d['matricule'], nom: $d['nom'], prenom: $d['prenom'],
                genre: $d['genre'], ddn: $d['ddn'],
                fonctionId: $fonctions[$sigleDirecteur],
                gradeId: $grades['HC'], catId: $categories['CL-X'], echelonId: $echelons[10],
                tiId: $tiRec, dps: $d['dps'], statut: 'actif',
            );
            $this->creerAffectation($directeur, Direction::class, $direction->id, $d['dps'], $adminId);
            $this->creerNomination($directeur, $d['poste'] ?? $direction->nom, Direction::class, $direction->id, $d['dps'], $adminId);
            $this->creerContrat($directeur, $tcCdi, $fonctions[$sigleDirecteur], $d['dps']);
            $this->creerUser($directeur, 'directeur');

            // ── Chef de service ───────────────────────────────────────────
            $cs = $h['chef_service'];
            $chefService = $this->creerAgent(
                matricule: $cs['matricule'], nom: $cs['nom'], prenom: $cs['prenom'],
                genre: $cs['genre'], ddn: $cs['ddn'],
                fonctionId: $fonctions['CS'],
                gradeId: $grades['INSP'], catId: $categories['CL-IX'], echelonId: $echelons[8],
                tiId: $tiRec, dps: $cs['dps'], statut: 'actif',
            );
            $this->creerAffectation($chefService, Service::class, $service->id, $cs['dps'], $adminId);
            $this->creerNomination($chefService, $service->nom, Service::class, $service->id, $cs['dps'], $adminId);
            $this->creerContrat($chefService, $tcCdi, $fonctions['CS'], $cs['dps']);
            $this->creerUser($chefService, 'chef-service');

            // ── Chef de bureau ────────────────────────────────────────────
            $cb = $h['chef_bureau'];
            $chefBureau = $this->creerAgent(
                matricule: $cb['matricule'], nom: $cb['nom'], prenom: $cb['prenom'],
                genre: $cb['genre'], ddn: $cb['ddn'],
                fonctionId: $fonctions['CB'],
                gradeId: $grades['INS'], catId: $categories['CL-VIII'], echelonId: $echelons[6],
                tiId: $tiRec, dps: $cb['dps'], statut: 'actif',
            );
            $this->creerAffectation($chefBureau, Bureau::class, $bureau->id, $cb['dps'], $adminId);
            $this->creerNomination($chefBureau, $bureau->nom, Bureau::class, $bureau->id, $cb['dps'], $adminId);
            $this->creerContrat($chefBureau, $tcCdi, $fonctions['CB'], $cb['dps']);
            $this->creerUser($chefBureau, $roleCbAgents ?? 'chef-bureau', $roleCbAgents ? $bureau->id : null);

            // ── Agents simples ────────────────────────────────────────────
            foreach ($h['agents'] as $ag) {
                $agent = $this->creerAgent(
                    matricule: $ag['matricule'], nom: $ag['nom'], prenom: $ag['prenom'],
                    genre: $ag['genre'], ddn: $ag['ddn'],
                    fonctionId: $fonctions['AGT'],
                    gradeId: $grades['VER'], catId: $categories['CL-VII'], echelonId: $echelons[3],
                    tiId: $tiRec, dps: $ag['dps'], statut: 'actif',
                );
                $this->creerAffectation($agent, Bureau::class, $bureau->id, $ag['dps'], $adminId);
                $this->creerContrat($agent, $tcCdi, $fonctions['AGT'], $ag['dps']);
                $this->creerUser($agent, $roleCbAgents ?? 'agent', $roleCbAgents ? $bureau->id : null);
            }

            // ── Stagiaire — pas de compte User (art. 46) ─────────────────
            $stg = $h['stagiaire'];
            $stagiaire = $this->creerAgent(
                matricule: $stg['matricule'], nom: $stg['nom'], prenom: $stg['prenom'],
                genre: $stg['genre'], ddn: $stg['ddn'],
                fonctionId: $fonctions['STG'],
                gradeId: $grades['COM'], catId: $categories['CL-III'], echelonId: $echelons[1],
                tiId: $tiStage, dps: $stg['dps'], statut: 'stagiaire',
            );
            $this->creerAffectation($stagiaire, Bureau::class, $bureau->id, $stg['dps'], $adminId);
            $this->creerContrat($stagiaire, $tcStage, $fonctions['STG'], $stg['dps'], $stg['fin']);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Résolution des structures
    // ─────────────────────────────────────────────────────────────────────

    private function resoudreService(Direction $direction, array $h): Service
    {
        return Service::firstOrCreate(
            ['sigle' => $h['service_sigle'], 'direction_id' => $direction->id],
            [
                'nom'          => $h['service_nom'] ?? $h['service_sigle'],
                'sigle'        => $h['service_sigle'],
                'direction_id' => $direction->id,
            ]
        );
    }

    private function resoudreBureau(Service $service, array $h): Bureau
    {
        return Bureau::firstOrCreate(
            ['sigle' => $h['bureau_sigle'], 'service_id' => $service->id],
            [
                'nom'        => $h['bureau_nom'] ?? $h['bureau_sigle'],
                'sigle'      => $h['bureau_sigle'],
                'service_id' => $service->id,
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Création des entités
    // ─────────────────────────────────────────────────────────────────────

    private function creerAgent(
        string $matricule, string $nom, string $prenom, string $genre,
        string $ddn, int $fonctionId, int $gradeId, int $catId,
        int $echelonId, int $tiId, string $dps, string $statut,
    ): Agent {
        return Agent::firstOrCreate(
            ['matricule' => $matricule],
            [
                'nom'                 => $nom,
                'prenom'              => $prenom,
                'date_naissance'      => $ddn,
                'lieu_naissance'      => 'Brazzaville',
                'nationalite'         => 'Congolaise',
                'genre'               => $genre,
                'grade_id'            => $gradeId,
                'categorie_id'        => $catId,
                'echelon_id'          => $echelonId,
                'fonction_id'         => $fonctionId,
                'type_integration_id' => $tiId,
                'date_prise_service'  => $dps,
                'statut'              => $statut,
            ]
        );
    }

    private function creerAffectation(
        Agent $agent, string $type, int $id, string $date, int $adminId,
    ): void {
        Affectation::firstOrCreate(
            ['agent_id' => $agent->id, 'structurable_type' => $type, 'structurable_id' => $id],
            [
                'motif'            => 'prise_de_service',
                'date_affectation' => $date,
                'statut'           => StatutAffectation::ACTIVE->value,
                'created_by'       => $adminId,
            ]
        );
    }

    private function creerNomination(
        Agent $agent, string $poste, string $type, int $id,
        string $dateDebut, int $adminId,
    ): void {
        Nomination::firstOrCreate(
            ['agent_id' => $agent->id, 'structurable_type' => $type, 'structurable_id' => $id],
            [
                'poste'          => $poste,
                'date_debut'     => $dateDebut,
                'type_acte'      => TypeActeNomination::DECISION->value,
                'statut'         => StatutNomination::ACTIVE->value,
                'soumis_a_essai' => false,
                'created_by'     => $adminId,
            ]
        );
    }

    private function creerContrat(
        Agent $agent, int $typeContratId, int $fonctionId,
        string $dateDebut, ?string $dateFin = null,
    ): void {
        Contrat::firstOrCreate(
            ['agent_id' => $agent->id, 'type_contrat_id' => $typeContratId, 'statut' => 'actif'],
            [
                'fonction_id' => $fonctionId,
                'date_debut'  => $dateDebut,
                'date_fin'    => $dateFin,
                'statut'      => 'actif',
            ]
        );
    }

    /**
     * Crée ou met à jour le compte User d'un agent permanent.
     * - Email  : prenom.nom@arft.cg (sans accents)
     * - Mot de passe : Nom@2026
     * - Rôle Spatie  : selon fonction / bureau
     * - bureau_id    : non nul uniquement pour les agents DRHL cloisonnés
     */
    private function creerUser(Agent $agent, string $role, ?int $bureauId = null): void
    {
        $email    = $this->email($agent->prenom, $agent->nom);
        $password = $this->password($agent->nom);

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'      => "{$agent->prenom} {$agent->nom}",
                'password'  => Hash::make($password),
                'agent_id'  => $agent->id,
                'bureau_id' => $bureauId,
                'is_active' => true,
            ]
        );

        // Mise à jour de l'agent_id si l'utilisateur existait déjà sans lien
        if (! $user->agent_id) {
            $user->update(['agent_id' => $agent->id, 'bureau_id' => $bureauId]);
        }

        $user->syncRoles([$role]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Utilitaires
    // ─────────────────────────────────────────────────────────────────────

    /** Génère l'email ARFT sans accents ni caractères spéciaux. */
    private function email(string $prenom, string $nom): string
    {
        $normalize = fn(string $s): string => strtr(
            strtolower($s),
            [
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'à' => 'a', 'â' => 'a', 'á' => 'a',
                'î' => 'i', 'ï' => 'i', 'í' => 'i',
                'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
                'û' => 'u', 'ù' => 'u', 'ü' => 'u', 'ú' => 'u',
                'ç' => 'c',
                ' ' => '.', '-' => '.',
                "'" => '',
            ]
        );

        return $normalize($prenom) . '.' . $normalize($nom) . '@arft.cg';
    }

    /** Mot de passe mémorable : Nomcapitalisé@2026 (ex: Moukala@2026) */
    private function password(string $nom): string
    {
        return ucfirst(strtolower($nom)) . '@2026';
    }
}
