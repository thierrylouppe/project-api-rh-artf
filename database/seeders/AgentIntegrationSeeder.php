<?php

namespace Database\Seeders;

use App\Enums\NiveauValidation;
use App\Enums\StatutAffectation;
use App\Enums\StatutDossier;
use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\CompteIntegration;
use App\Models\Contrat;
use App\Models\Direction;
use App\Models\DocumentDossier;
use App\Models\DossierIntegration;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\Categorie;
use App\Models\Echelon;
use App\Models\HistoriqueIntegration;
use App\Models\Nomination;
use App\Models\PriseDeService;
use App\Models\Service;
use App\Models\TypeContrat;
use App\Models\TypeDocument;
use App\Models\TypeIntegration;
use App\Models\User;
use App\Models\Diplome;
use App\Models\InformationsProfessionnelle;
use App\Models\ValidationWorkflow;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AgentIntegrationSeeder — Workflow complet
 *
 * Crée 51 agents (41 permanents + 10 stagiaires) avec la chaîne
 * d'intégration réelle de bout en bout :
 *
 *   Agent → DossierIntegration (15 états) → DocumentDossier (pièces obligatoires)
 *         → ValidationWorkflow (vrais IDs validateurs)
 *         → HistoriqueIntegration (chaque transition)
 *         → Affectation (3 états + ValidationWorkflow)
 *         → Nomination  (3 états + ValidationWorkflow) — postes responsabilité
 *         → Contrat (actif)
 *         → PriseDeService
 *         → CompteIntegration ↔ User
 *
 * Validateurs réels :
 *   chef_bureau      → CB de la direction concernée
 *   chef_service     → CS de la direction concernée
 *   directeur        → Directeur de la direction
 *   drh              → Directeur DRHL (ARFT-00017 — Lydiane GAMBOU)
 *   directeur_general→ DG (ARFT-00001 — Jean-Pierre MOUKALA)
 */
class AgentIntegrationSeeder extends Seeder
{
    // ── Rôle DRHL spécifique par bureau ─────────────────────────────────
    private const ROLES_DRHL = [
        'B.P'    => 'rh-personnel',
        'B.F'    => 'rh-formation',
        'B.S.'   => 'rh-solde',
        'B.A.S.' => 'rh-affaires-sociales',
        'B.PL'   => 'rh-etude',
    ];

    // Référentiels chargés en run()
    private array $grades      = [];
    private array $categories  = [];
    private array $echelons    = [];
    private array $fonctions   = [];
    private array $typesDocs   = [];
    private int   $tiRecId     = 0;
    private int   $tiStageId   = 0;
    private int   $tcCdiId     = 0;
    private int   $tcStageId   = 0;
    private int   $adminId     = 0;
    private int   $dossierSeq  = 0; // séquence référence dossier

    // IDs des validateurs (chargés après création des premiers agents)
    private int $dgUserId      = 0;
    private int $drhUserId     = 0; // Directeur DRHL
    private int $adminUserId   = 0;

    // ─────────────────────────────────────────────────────────────────────
    // Données fixes — 10 directions (identiques à la version précédente)
    // ─────────────────────────────────────────────────────────────────────
    private function hierarchies(): array
    {
        $debutStage = Carbon::now()->subMonths(3)->toDateString();
        $finStage   = Carbon::now()->addMonths(3)->toDateString();

        return [
            // ══ D.R.H.L EN PREMIER ══ pour que Lydiane GAMBOU soit le
            // validateur DRH pour toutes les directions suivantes.
            [
                'direction' => 'D.R.H.L', 'service_sigle' => 'S.R.H', 'bureau_sigle' => 'B.F',
                'directeur'    => ['matricule'=>'ARFT-00017','nom'=>'GAMBOU',      'prenom'=>'Lydiane',   'genre'=>'F','ddn'=>'1971-12-02','poste'=>'DIRECTION DES RESSOURCES HUMAINES ET DE LA LOGISTIQUE','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00018','nom'=>'MILANDOU',    'prenom'=>'Hervé',     'genre'=>'M','ddn'=>'1977-06-25','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00019','nom'=>'MOUNANGA',    'prenom'=>'Rosette',   'genre'=>'F','ddn'=>'1985-03-11','dps'=>'2017-06-01'],
                'agents'       => [
                    ['matricule'=>'ARFT-00020','nom'=>'LOUBAKI',  'prenom'=>'Yves',    'genre'=>'M','ddn'=>'1993-08-28','dps'=>'2020-05-04'],
                    ['matricule'=>'ARFT-00021','nom'=>'NKOUNKOU', 'prenom'=>'Gilberte','genre'=>'F','ddn'=>'1990-02-06','dps'=>'2020-05-04'],
                ],
                'stagiaire' => ['matricule'=>'STG-0004','nom'=>'BIKOUTA','prenom'=>'Fulgence','genre'=>'M','ddn'=>'2002-11-19','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.G', 'service_sigle' => 'S.S.I', 'bureau_sigle' => 'B.EXP',
                'directeur'    => ['matricule'=>'ARFT-00002','nom'=>'NZABA',       'prenom'=>'Marie-Rose', 'genre'=>'F','ddn'=>'1972-07-12','poste'=>'DIRECTION GENERALE','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00003','nom'=>'NGOMA',       'prenom'=>'Emmanuel',  'genre'=>'M','ddn'=>'1978-02-28','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00004','nom'=>'MBEMBA',      'prenom'=>'Parfait',   'genre'=>'M','ddn'=>'1982-11-05','dps'=>'2017-06-01'],
                'agents'       => [
                    ['matricule'=>'ARFT-00005','nom'=>'LOEMBA',  'prenom'=>'Solange', 'genre'=>'F','ddn'=>'1990-04-18','dps'=>'2020-01-06'],
                    ['matricule'=>'ARFT-00006','nom'=>'KIBANGOU','prenom'=>'Fernand', 'genre'=>'M','ddn'=>'1988-09-22','dps'=>'2020-01-06'],
                ],
                'stagiaire' => ['matricule'=>'STG-0001','nom'=>'MALONGA','prenom'=>'Christelle','genre'=>'F','ddn'=>'2002-06-14','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.F', 'service_sigle' => 'S.B', 'bureau_sigle' => 'B.RCT',
                'directeur'    => ['matricule'=>'ARFT-00007','nom'=>'BIYOUDI',     'prenom'=>'Pierre',    'genre'=>'M','ddn'=>'1970-09-08','poste'=>'DIRECTION FINANCIERE','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00008','nom'=>'ONDONGO',     'prenom'=>'Aimée',     'genre'=>'F','ddn'=>'1976-03-15','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00009','nom'=>'NSIMBA',      'prenom'=>'Cédric',    'genre'=>'M','ddn'=>'1983-07-20','dps'=>'2017-06-01'],
                'agents'       => [
                    ['matricule'=>'ARFT-00010','nom'=>'MOUAMBA','prenom'=>'Nadège',   'genre'=>'F','ddn'=>'1991-01-30','dps'=>'2020-03-02'],
                    ['matricule'=>'ARFT-00011','nom'=>'NKAYA',  'prenom'=>'Rodrigue', 'genre'=>'M','ddn'=>'1987-12-05','dps'=>'2020-03-02'],
                ],
                'stagiaire' => ['matricule'=>'STG-0002','nom'=>'BANZOUZI','prenom'=>'Sylvestre','genre'=>'M','ddn'=>'2003-04-22','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.R', 'service_sigle' => 'S.A.R', 'bureau_sigle' => 'B.O.M.M.A',
                'directeur'    => ['matricule'=>'ARFT-00012','nom'=>'MAVOUNGOU',   'prenom'=>'Alvine',    'genre'=>'F','ddn'=>'1973-05-18','poste'=>'DIRECTION DE LA REGULATION','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00013','nom'=>'MAMPOUYA',    'prenom'=>'Rémy',      'genre'=>'M','ddn'=>'1979-08-10','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00014','nom'=>'NGOUBILI',    'prenom'=>'Carmélie',  'genre'=>'F','ddn'=>'1984-02-25','dps'=>'2017-06-01'],
                'agents'       => [
                    ['matricule'=>'ARFT-00015','nom'=>'TSIBA', 'prenom'=>'Bertrand','genre'=>'M','ddn'=>'1992-07-14','dps'=>'2020-04-01'],
                    ['matricule'=>'ARFT-00016','nom'=>'ITOUA', 'prenom'=>'Déborah', 'genre'=>'F','ddn'=>'1989-11-03','dps'=>'2020-04-01'],
                ],
                'stagiaire' => ['matricule'=>'STG-0003','nom'=>'POATY','prenom'=>'Serge','genre'=>'M','ddn'=>'2001-09-17','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'A.C', 'service_sigle' => 'S.D', 'bureau_sigle' => 'B.REC',
                'directeur'    => ['matricule'=>'ARFT-00022','nom'=>'BAHAMBOULA',  'prenom'=>'Sylvie',    'genre'=>'F','ddn'=>'1969-04-30','poste'=>'AGENCE COMPTABLE','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00023','nom'=>'MANTSANGA',   'prenom'=>'Hyacinthe', 'genre'=>'M','ddn'=>'1975-10-17','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00024','nom'=>'MOUSSOKI',    'prenom'=>'Marcelline','genre'=>'F','ddn'=>'1981-06-08','dps'=>'2017-06-01'],
                'agents'       => [
                    ['matricule'=>'ARFT-00025','nom'=>'MAFOUTA',  'prenom'=>'Jocelyn','genre'=>'M','ddn'=>'1994-03-23','dps'=>'2020-06-01'],
                    ['matricule'=>'ARFT-00026','nom'=>'DZABATOU', 'prenom'=>'Carine', 'genre'=>'F','ddn'=>'1991-09-14','dps'=>'2020-06-01'],
                ],
                'stagiaire' => ['matricule'=>'STG-0005','nom'=>'BOUITI','prenom'=>'Théodore','genre'=>'M','ddn'=>'2003-07-01','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.I.S.E', 'service_sigle' => 'S.E', 'bureau_sigle' => 'B.E.G',
                'directeur'    => ['matricule'=>'ARFT-00027','nom'=>'LOUBOTA',     'prenom'=>'Michèle',   'genre'=>'F','ddn'=>'1974-01-27','poste'=>"DIRECTION DE L'INSPECTION DES STAT. ET DES ETUDES",'dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00028','nom'=>'OSSOKO',      'prenom'=>'Dieudonné', 'genre'=>'M','ddn'=>'1980-05-14','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00029','nom'=>'OTIOBANDA',   'prenom'=>'Irène',     'genre'=>'F','ddn'=>'1986-10-09','dps'=>'2017-06-01'],
                'agents'       => [
                    ['matricule'=>'ARFT-00030','nom'=>'MAYINDOU', 'prenom'=>'Lionel',    'genre'=>'M','ddn'=>'1995-04-16','dps'=>'2021-01-04'],
                    ['matricule'=>'ARFT-00031','nom'=>'NGATSONO', 'prenom'=>'Bénédicte', 'genre'=>'F','ddn'=>'1992-12-31','dps'=>'2021-01-04'],
                ],
                'stagiaire' => ['matricule'=>'STG-0006','nom'=>'KIMBOUALA','prenom'=>'Ariel','genre'=>'M','ddn'=>'2004-02-28','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.A.J.I.C', 'service_sigle' => 'S.A.J', 'bureau_sigle' => 'B.J',
                'bureau_nom' => 'BUREAU JURIDIQUE',
                'directeur'    => ['matricule'=>'ARFT-00032','nom'=>'BITSINDOU',   'prenom'=>'Florence',  'genre'=>'F','ddn'=>'1975-08-23','poste'=>'DIRECTION DES AFF. JURIDIQUES, DES INVEST. ET DE LA COOP.','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00033','nom'=>'NGANDZALI',   'prenom'=>'Wilfried',  'genre'=>'M','ddn'=>'1981-03-07','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00034','nom'=>'BOUDZOUMOU',  'prenom'=>'Nadège',    'genre'=>'F','ddn'=>'1987-06-15','dps'=>'2018-01-02'],
                'agents'       => [
                    ['matricule'=>'ARFT-00035','nom'=>'MADZOU',  'prenom'=>'Stève', 'genre'=>'M','ddn'=>'1994-10-11','dps'=>'2021-03-01'],
                    ['matricule'=>'ARFT-00036','nom'=>'KIMFOKO', 'prenom'=>'Agnès', 'genre'=>'F','ddn'=>'1993-05-22','dps'=>'2021-03-01'],
                ],
                'stagiaire' => ['matricule'=>'STG-0007','nom'=>'NZINGA','prenom'=>'Calixte','genre'=>'M','ddn'=>'2005-01-09','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.D.P.N', 'service_sigle' => 'S.ADM.PN',
                'service_nom' => 'SERVICE ADMINISTRATIF POINTE-NOIRE',
                'bureau_sigle' => 'B.ADM.PN', 'bureau_nom' => 'BUREAU ADMINISTRATIF POINTE-NOIRE',
                'directeur'    => ['matricule'=>'ARFT-00037','nom'=>'MOUKENGUE',   'prenom'=>'Victoire',  'genre'=>'F','ddn'=>'1976-02-11','poste'=>'DIRECTION DEPARTEMENTALE POINTE-NOIRE','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00038','nom'=>'MFOUBOU',     'prenom'=>'Clovis',    'genre'=>'M','ddn'=>'1983-09-19','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00039','nom'=>'LOUZOLO',     'prenom'=>'Joséphine', 'genre'=>'F','ddn'=>'1989-04-27','dps'=>'2018-01-02'],
                'agents'       => [
                    ['matricule'=>'ARFT-00040','nom'=>'NGAKOSSO',   'prenom'=>'Roch',    'genre'=>'M','ddn'=>'1996-07-08','dps'=>'2021-06-01'],
                    ['matricule'=>'ARFT-00041','nom'=>'BABINDAMANA','prenom'=>'Viviane', 'genre'=>'F','ddn'=>'1993-11-25','dps'=>'2021-06-01'],
                ],
                'stagiaire' => ['matricule'=>'STG-0008','nom'=>'MBILO','prenom'=>'Dieu-Merci','genre'=>'M','ddn'=>'2004-08-13','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.D.O', 'service_sigle' => 'S.ADM.O',
                'service_nom' => 'SERVICE ADMINISTRATIF OUESSO',
                'bureau_sigle' => 'B.ADM.O', 'bureau_nom' => 'BUREAU ADMINISTRATIF OUESSO',
                'directeur'    => ['matricule'=>'ARFT-00042','nom'=>'MAKAYA',      'prenom'=>'Gaston',    'genre'=>'M','ddn'=>'1977-06-29','poste'=>'DIRECTION DEPARTEMENTALE OUESSO','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00043','nom'=>'NGOUMA',      'prenom'=>'Arlette',   'genre'=>'F','ddn'=>'1985-01-14','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00044','nom'=>'TATY',        'prenom'=>'Honoré',    'genre'=>'M','ddn'=>'1990-07-03','dps'=>'2018-01-02'],
                'agents'       => [
                    ['matricule'=>'ARFT-00045','nom'=>'FOUATOU',   'prenom'=>'Espérance','genre'=>'F','ddn'=>'1997-03-21','dps'=>'2022-01-03'],
                    ['matricule'=>'ARFT-00046','nom'=>'LOUBASSOU', 'prenom'=>'Noël',     'genre'=>'M','ddn'=>'1995-12-25','dps'=>'2022-01-03'],
                ],
                'stagiaire' => ['matricule'=>'STG-0009','nom'=>'MBOSSO','prenom'=>'Flore','genre'=>'F','ddn'=>'2005-05-17','dps'=>$debutStage,'fin'=>$finStage],
            ],
            [
                'direction' => 'D.D.D', 'service_sigle' => 'S.ADM.D',
                'service_nom' => 'SERVICE ADMINISTRATIF DOLISIE',
                'bureau_sigle' => 'B.ADM.D', 'bureau_nom' => 'BUREAU ADMINISTRATIF DOLISIE',
                'directeur'    => ['matricule'=>'ARFT-00047','nom'=>'DIAKABANA',   'prenom'=>'Cédric',    'genre'=>'M','ddn'=>'1980-11-14','poste'=>'DIRECTION DEPARTEMENTALE DOLISIE','dps'=>'2015-03-01'],
                'chef_service' => ['matricule'=>'ARFT-00048','nom'=>'BATAMBILA',   'prenom'=>'Jacqueline','genre'=>'F','ddn'=>'1986-04-08','dps'=>'2016-09-01'],
                'chef_bureau'  => ['matricule'=>'ARFT-00049','nom'=>'NKEOUA',      'prenom'=>'Ghislain',  'genre'=>'M','ddn'=>'1991-09-22','dps'=>'2018-01-02'],
                'agents'       => [
                    ['matricule'=>'ARFT-00050','nom'=>'MANKESSI', 'prenom'=>'Célestine','genre'=>'F','ddn'=>'1998-06-30','dps'=>'2022-03-07'],
                    ['matricule'=>'ARFT-00051','nom'=>'NGANGA',   'prenom'=>'Calvin',   'genre'=>'M','ddn'=>'1996-02-18','dps'=>'2022-03-07'],
                ],
                'stagiaire' => ['matricule'=>'STG-0010','nom'=>'MOUSSELE','prenom'=>'Marguerite','genre'=>'F','ddn'=>'2006-10-04','dps'=>$debutStage,'fin'=>$finStage],
            ],
        ];
    }

    // ═════════════════════════════════════════════════════════════════════
    // POINT D'ENTRÉE
    // ═════════════════════════════════════════════════════════════════════

    public function run(): void
    {
        // ── Référentiels ─────────────────────────────────────────────────
        $this->grades     = Grade::pluck('id', 'sigle')->all();
        $this->categories = Categorie::pluck('id', 'sigle')->all();
        $this->echelons   = Echelon::pluck('id', 'numero')->all();
        $this->fonctions  = Fonction::pluck('id', 'sigle')->all();
        $this->typesDocs  = TypeDocument::pluck('id', 'nom')->all();
        $this->tiRecId    = TypeIntegration::where('nom', 'Recrutement externe')->value('id');
        $this->tiStageId  = TypeIntegration::where('nom', 'Stage professionnel')->value('id');
        $this->tcCdiId    = TypeContrat::where('sigle', 'CDI')->value('id');
        $this->tcStageId  = TypeContrat::where('sigle', 'STG')->value('id');
        $adminUser        = User::where('email', 'admin@artf.cg')->first();
        $this->adminId    = $adminUser->id;
        $this->adminUserId = $adminUser->id;

        // ── 1. Directeur Général — workflow simplifié (validé par admin) ─
        $dirDG = Direction::where('sigle', 'D.G')->firstOrFail();

        $dg = $this->creerAgent(
            'ARFT-00001', 'MOUKALA', 'Jean-Pierre', 'M', '1965-03-15',
            $this->fonctions['DG'], $this->grades['HC'], $this->categories['CL-X'],
            $this->echelons[12], $this->tiRecId, '2010-01-15', 'actif',
        );
        $this->creerInfosPro($dg, 'DOC', 'Administration Publique', 'Université Marien Ngouabi', 20);
        $dgUser = $this->creerUser($dg, 'directeur-general');
        $dgUser->assignRole('admin'); // DG = accès système complet pour les tests
        $this->dgUserId  = $dgUser->id;
        $this->drhUserId = $dgUser->id; // sera mis à jour après création du DC DRHL

        // Dossier DG validé par admin seulement
        $dossierDg = $this->creerDossierIntegration(
            agent: $dg, typeId: $this->tiRecId,
            structurableType: Direction::class, structurableId: $dirDG->id,
            poste: 'Directeur Général', annee: '2010',
            demandeurId: $this->adminUserId,
            validateurs: [
                [NiveauValidation::DIRECTEUR_GENERAL, $this->adminUserId, '2010-01-10'],
            ],
            datePrise: '2010-01-15',
        );
        $this->creerContrat($dg, $this->tcCdiId, $this->fonctions['DG'], '2010-01-15');
        $this->creerAffectationAvecWorkflow(
            agent: $dg, structurableType: Direction::class, structurableId: $dirDG->id,
            dateAffectation: '2010-01-15',
            validateurs: [[$this->adminUserId, '2010-01-12']],
        );
        $this->creerNominationAvecWorkflow(
            agent: $dg, poste: 'Directeur Général',
            structurableType: Direction::class, structurableId: $dirDG->id,
            dateDebut: '2010-01-15',
            validateurs: [[$this->adminUserId, '2010-01-13']],
        );
        $this->creerPriseDeService($dg, $dossierDg, $dg->id, '2010-01-15');
        $this->creerCompteIntegration($dg, $dgUser, '2010-01-15');

        // ── 2. Hiérarchie par direction ──────────────────────────────────
        foreach ($this->hierarchies() as $h) {
            $direction = Direction::where('sigle', $h['direction'])->first();
            if (! $direction) { continue; }

            $service = $this->resoudreService($direction, $h);
            $bureau  = $this->resoudreBureau($service, $h);
            $isDep   = str_starts_with($h['direction'], 'D.D');
            $sigleFonctDir = $isDep ? 'DD' : 'DC';
            $roleCbAgents  = self::ROLES_DRHL[$h['bureau_sigle']] ?? null;

            // ── DIRECTEUR ────────────────────────────────────────────────
            $d = $h['directeur'];
            $directeur = $this->creerAgent(
                $d['matricule'], $d['nom'], $d['prenom'], $d['genre'], $d['ddn'],
                $this->fonctions[$sigleFonctDir], $this->grades['HC'],
                $this->categories['CL-X'], $this->echelons[10],
                $this->tiRecId, $d['dps'], 'actif',
            );
            $this->creerInfosPro($directeur, 'DOC', 'Management & Administration', 'Université Marien Ngouabi', 15);
            $directeurUser = $this->creerUser($directeur, 'directeur');

            // Si c'est la DRHL, mettre à jour le validateur DRH
            if ($h['direction'] === 'D.R.H.L') {
                $this->drhUserId = $directeurUser->id;
                $directeurUser->assignRole('rh'); // DRHL Director = accès métier RH complet
            }

            $dossierDir = $this->creerDossierIntegration(
                agent: $directeur, typeId: $this->tiRecId,
                structurableType: Direction::class, structurableId: $direction->id,
                poste: $d['poste'] ?? $direction->nom, annee: substr($d['dps'], 0, 4),
                demandeurId: $this->adminUserId,
                validateurs: [
                    [NiveauValidation::DRH,              $this->drhUserId,  Carbon::parse($d['dps'])->subDays(7)->toDateString()],
                    [NiveauValidation::DIRECTEUR_GENERAL, $this->dgUserId,  Carbon::parse($d['dps'])->subDays(3)->toDateString()],
                ],
                datePrise: $d['dps'],
            );
            $this->creerContrat($directeur, $this->tcCdiId, $this->fonctions[$sigleFonctDir], $d['dps']);
            $this->creerAffectationAvecWorkflow(
                agent: $directeur, structurableType: Direction::class, structurableId: $direction->id,
                dateAffectation: $d['dps'],
                validateurs: [
                    [$this->drhUserId, Carbon::parse($d['dps'])->subDays(5)->toDateString()],
                    [$this->dgUserId,  Carbon::parse($d['dps'])->subDays(2)->toDateString()],
                ],
            );
            $this->creerNominationAvecWorkflow(
                agent: $directeur, poste: $d['poste'] ?? $direction->nom,
                structurableType: Direction::class, structurableId: $direction->id,
                dateDebut: $d['dps'],
                validateurs: [
                    [$this->drhUserId, Carbon::parse($d['dps'])->subDays(4)->toDateString()],
                    [$this->dgUserId,  Carbon::parse($d['dps'])->subDays(1)->toDateString()],
                ],
            );
            $this->creerPriseDeService($directeur, $dossierDir, $dg->id, $d['dps']);
            $this->creerCompteIntegration($directeur, $directeurUser, $d['dps']);

            // ── CHEF DE SERVICE ───────────────────────────────────────────
            $cs = $h['chef_service'];
            $chefService = $this->creerAgent(
                $cs['matricule'], $cs['nom'], $cs['prenom'], $cs['genre'], $cs['ddn'],
                $this->fonctions['CS'], $this->grades['INSP'],
                $this->categories['CL-IX'], $this->echelons[8],
                $this->tiRecId, $cs['dps'], 'actif',
            );
            $this->creerInfosPro($chefService, 'DOC', 'Gestion des Ressources Humaines', 'Université Marien Ngouabi', 12);
            $csUser = $this->creerUser($chefService, 'chef-service');

            $dossierCs = $this->creerDossierIntegration(
                agent: $chefService, typeId: $this->tiRecId,
                structurableType: Service::class, structurableId: $service->id,
                poste: 'Chef de service — '.$service->nom, annee: substr($cs['dps'], 0, 4),
                demandeurId: $directeurUser->id,
                validateurs: [
                    [NiveauValidation::DIRECTEUR,         $directeurUser->id, Carbon::parse($cs['dps'])->subDays(10)->toDateString()],
                    [NiveauValidation::DRH,               $this->drhUserId,   Carbon::parse($cs['dps'])->subDays(6)->toDateString()],
                    [NiveauValidation::DIRECTEUR_GENERAL,  $this->dgUserId,   Carbon::parse($cs['dps'])->subDays(3)->toDateString()],
                ],
                datePrise: $cs['dps'],
            );
            $this->creerContrat($chefService, $this->tcCdiId, $this->fonctions['CS'], $cs['dps']);
            $this->creerAffectationAvecWorkflow(
                agent: $chefService, structurableType: Service::class, structurableId: $service->id,
                dateAffectation: $cs['dps'],
                validateurs: [
                    [$directeurUser->id, Carbon::parse($cs['dps'])->subDays(7)->toDateString()],
                    [$this->dgUserId,    Carbon::parse($cs['dps'])->subDays(2)->toDateString()],
                ],
            );
            $this->creerNominationAvecWorkflow(
                agent: $chefService, poste: $service->nom,
                structurableType: Service::class, structurableId: $service->id,
                dateDebut: $cs['dps'],
                validateurs: [
                    [$directeurUser->id, Carbon::parse($cs['dps'])->subDays(5)->toDateString()],
                    [$this->dgUserId,    Carbon::parse($cs['dps'])->subDays(1)->toDateString()],
                ],
            );
            $this->creerPriseDeService($chefService, $dossierCs, $directeur->id, $cs['dps']);
            $this->creerCompteIntegration($chefService, $csUser, $cs['dps']);

            // ── CHEF DE BUREAU ────────────────────────────────────────────
            $cb = $h['chef_bureau'];
            $chefBureau = $this->creerAgent(
                $cb['matricule'], $cb['nom'], $cb['prenom'], $cb['genre'], $cb['ddn'],
                $this->fonctions['CB'], $this->grades['INS'],
                $this->categories['CL-VIII'], $this->echelons[6],
                $this->tiRecId, $cb['dps'], 'actif',
            );
            $this->creerInfosPro($chefBureau, 'MST', 'Administration & Finances', 'Université Marien Ngouabi', 8);
            $cbUser = $this->creerUser($chefBureau, $roleCbAgents ?? 'chef-bureau', $roleCbAgents ? $bureau->id : null);

            $dossierCb = $this->creerDossierIntegration(
                agent: $chefBureau, typeId: $this->tiRecId,
                structurableType: Bureau::class, structurableId: $bureau->id,
                poste: 'Chef de bureau — '.$bureau->nom, annee: substr($cb['dps'], 0, 4),
                demandeurId: $csUser->id,
                validateurs: [
                    [NiveauValidation::CHEF_SERVICE,      $csUser->id,         Carbon::parse($cb['dps'])->subDays(12)->toDateString()],
                    [NiveauValidation::DIRECTEUR,          $directeurUser->id,  Carbon::parse($cb['dps'])->subDays(8)->toDateString()],
                    [NiveauValidation::DRH,                $this->drhUserId,    Carbon::parse($cb['dps'])->subDays(5)->toDateString()],
                    [NiveauValidation::DIRECTEUR_GENERAL,  $this->dgUserId,     Carbon::parse($cb['dps'])->subDays(2)->toDateString()],
                ],
                datePrise: $cb['dps'],
            );
            $this->creerContrat($chefBureau, $this->tcCdiId, $this->fonctions['CB'], $cb['dps']);
            $this->creerAffectationAvecWorkflow(
                agent: $chefBureau, structurableType: Bureau::class, structurableId: $bureau->id,
                dateAffectation: $cb['dps'],
                validateurs: [
                    [$csUser->id,        Carbon::parse($cb['dps'])->subDays(9)->toDateString()],
                    [$directeurUser->id, Carbon::parse($cb['dps'])->subDays(4)->toDateString()],
                ],
            );
            $this->creerNominationAvecWorkflow(
                agent: $chefBureau, poste: $bureau->nom,
                structurableType: Bureau::class, structurableId: $bureau->id,
                dateDebut: $cb['dps'],
                validateurs: [
                    [$csUser->id,        Carbon::parse($cb['dps'])->subDays(6)->toDateString()],
                    [$directeurUser->id, Carbon::parse($cb['dps'])->subDays(2)->toDateString()],
                ],
            );
            $this->creerPriseDeService($chefBureau, $dossierCb, $chefService->id, $cb['dps']);
            $this->creerCompteIntegration($chefBureau, $cbUser, $cb['dps']);

            // ── AGENTS SIMPLES (×2) ───────────────────────────────────────
            foreach ($h['agents'] as $ag) {
                $agent = $this->creerAgent(
                    $ag['matricule'], $ag['nom'], $ag['prenom'], $ag['genre'], $ag['ddn'],
                    $this->fonctions['AGT'], $this->grades['VER'],
                    $this->categories['CL-VII'], $this->echelons[3],
                    $this->tiRecId, $ag['dps'], 'actif',
                );
                $this->creerInfosPro($agent, 'LIC', 'Sciences Économiques', 'Université Marien Ngouabi', 3);
                $agUser = $this->creerUser($agent, $roleCbAgents ?? 'agent', $roleCbAgents ? $bureau->id : null);

                $dossierAg = $this->creerDossierIntegration(
                    agent: $agent, typeId: $this->tiRecId,
                    structurableType: Bureau::class, structurableId: $bureau->id,
                    poste: 'Agent — '.$bureau->nom, annee: substr($ag['dps'], 0, 4),
                    demandeurId: $cbUser->id,
                    validateurs: [
                        [NiveauValidation::CHEF_BUREAU,       $cbUser->id,         Carbon::parse($ag['dps'])->subDays(18)->toDateString()],
                        [NiveauValidation::CHEF_SERVICE,      $csUser->id,         Carbon::parse($ag['dps'])->subDays(14)->toDateString()],
                        [NiveauValidation::DIRECTEUR,          $directeurUser->id,  Carbon::parse($ag['dps'])->subDays(10)->toDateString()],
                        [NiveauValidation::DRH,                $this->drhUserId,    Carbon::parse($ag['dps'])->subDays(6)->toDateString()],
                        [NiveauValidation::DIRECTEUR_GENERAL,  $this->dgUserId,     Carbon::parse($ag['dps'])->subDays(2)->toDateString()],
                    ],
                    datePrise: $ag['dps'],
                );
                $this->creerContrat($agent, $this->tcCdiId, $this->fonctions['AGT'], $ag['dps']);
                $this->creerAffectationAvecWorkflow(
                    agent: $agent, structurableType: Bureau::class, structurableId: $bureau->id,
                    dateAffectation: $ag['dps'],
                    validateurs: [
                        [$cbUser->id,        Carbon::parse($ag['dps'])->subDays(12)->toDateString()],
                        [$csUser->id,        Carbon::parse($ag['dps'])->subDays(8)->toDateString()],
                        [$directeurUser->id, Carbon::parse($ag['dps'])->subDays(3)->toDateString()],
                    ],
                );
                $this->creerPriseDeService($agent, $dossierAg, $chefBureau->id, $ag['dps']);
                $this->creerCompteIntegration($agent, $agUser, $ag['dps']);
            }

            // ── STAGIAIRE — pas de compte (art. 46) ──────────────────────
            $stg = $h['stagiaire'];
            $stagiaire = $this->creerAgent(
                $stg['matricule'], $stg['nom'], $stg['prenom'], $stg['genre'], $stg['ddn'],
                $this->fonctions['STG'], $this->grades['COM'],
                $this->categories['CL-III'], $this->echelons[1],
                $this->tiStageId, $stg['dps'], 'stagiaire',
            );
            $this->creerInfosPro($stagiaire, 'BTS', 'Gestion Commerciale', 'Institut Supérieur de Gestion', 0);
            // Circuit stage : CS → Directeur → DRH
            $dossierStg = $this->creerDossierIntegration(
                agent: $stagiaire, typeId: $this->tiStageId,
                structurableType: Bureau::class, structurableId: $bureau->id,
                poste: 'Stagiaire — '.$bureau->nom, annee: substr($stg['dps'], 0, 4),
                demandeurId: $csUser->id,
                validateurs: [
                    [NiveauValidation::CHEF_SERVICE, $csUser->id,        Carbon::parse($stg['dps'])->subDays(10)->toDateString()],
                    [NiveauValidation::DIRECTEUR,     $directeurUser->id, Carbon::parse($stg['dps'])->subDays(6)->toDateString()],
                    [NiveauValidation::DRH,           $this->drhUserId,   Carbon::parse($stg['dps'])->subDays(3)->toDateString()],
                ],
                datePrise: $stg['dps'],
                dateFin: $stg['fin'],
            );
            $this->creerContrat($stagiaire, $this->tcStageId, $this->fonctions['STG'], $stg['dps'], $stg['fin']);
            $this->creerAffectationAvecWorkflow(
                agent: $stagiaire, structurableType: Bureau::class, structurableId: $bureau->id,
                dateAffectation: $stg['dps'],
                validateurs: [
                    [$csUser->id,        Carbon::parse($stg['dps'])->subDays(7)->toDateString()],
                    [$directeurUser->id, Carbon::parse($stg['dps'])->subDays(2)->toDateString()],
                ],
            );
            $this->creerPriseDeService($stagiaire, $dossierStg, $chefBureau->id, $stg['dps']);
        }
    }

    // ═════════════════════════════════════════════════════════════════════
    // HELPERS — WORKFLOW
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Crée un DossierIntegration complet avec :
     *  - Progression des 15 statuts (BROUILLON → INTEGRE)
     *  - DocumentDossier (toutes les pièces obligatoires du type d'intégration)
     *  - ValidationWorkflow (circuit réel avec vrais IDs)
     *  - HistoriqueIntegration pour chaque transition
     */
    private function creerDossierIntegration(
        Agent  $agent,
        int    $typeId,
        string $structurableType,
        int    $structurableId,
        string $poste,
        string $annee,
        int    $demandeurId,
        array  $validateurs,     // [[NiveauValidation|null, userId, dateDecision], ...]
        string $datePrise,
        ?string $dateFin = null,
    ): DossierIntegration {
        $this->dossierSeq++;
        $ref = sprintf('DOS/ARFT/%s/%04d', $annee, $this->dossierSeq);

        $dossier = DossierIntegration::firstOrCreate(
            ['agent_id' => $agent->id],
            [
                'reference'            => $ref,
                'type_integration_id'  => $typeId,
                'demandeur_id'         => $demandeurId,
                'structurable_type'    => $structurableType,
                'structurable_id'      => $structurableId,
                'poste_demande'        => $poste,
                'nombre_postes'        => 1,
                'statut'               => StatutDossier::INTEGRE->value,
                'date_demande'         => Carbon::parse($datePrise)->subMonths(2)->toDateString(),
                'motif'                => 'Recrutement — poste vacant',
                'deja_salarie'         => false,
            ]
        );

        // ── Documents obligatoires ────────────────────────────────────────
        $this->creerDocumentsDossier($dossier, $agent, $typeId, $demandeurId, $datePrise);

        // ── ValidationWorkflow ────────────────────────────────────────────
        foreach ($validateurs as $idx => [$niveau, $userId, $dateDecision]) {
            ValidationWorkflow::firstOrCreate(
                [
                    'validable_type' => DossierIntegration::class,
                    'validable_id'   => $dossier->id,
                    'ordre'          => $idx + 1,
                ],
                [
                    'niveau'        => $niveau instanceof NiveauValidation ? $niveau->value : $niveau,
                    'validateur_id' => $userId,
                    'statut'        => 'approuve',
                    'commentaire'   => 'Dossier complet et conforme.',
                    'date_decision' => $dateDecision,
                ]
            );
        }

        // ── Historique des transitions clés ──────────────────────────────
        $etapes = [
            ['BROUILLON',      'SOUMIS',            'soumission_dossier',       'Dossier soumis par le demandeur.'],
            ['SOUMIS',         'EN_ETUDE_RH',        'prise_en_charge_rh',       'Dossier pris en charge par la DRHL.'],
            ['EN_ETUDE_RH',    'DOSSIER_COMPLET',    'validation_documents',     'Tous les documents obligatoires présents et validés.'],
            ['DOSSIER_COMPLET','VALIDE_RH',           'validation_rh',            'Dossier validé par le service RH.'],
            ['VALIDE_RH',      'EN_ATTENTE_DG',      'transmission_dg',          'Dossier transmis pour validation DG.'],
            ['EN_ATTENTE_DG',  'VALIDE_DG',          'validation_dg',            'Dossier validé par le Directeur Général.'],
            ['VALIDE_DG',      'ACTE_GENERE',        'generation_acte',          'Acte administratif généré.'],
            ['ACTE_GENERE',    'CONTRAT_SIGNE',      'signature_contrat',        'Contrat signé par les deux parties.'],
            ['CONTRAT_SIGNE',  'MATRICULE_CREE',     'creation_matricule',       "Matricule {$agent->matricule} attribué."],
            ['MATRICULE_CREE', 'AFFECTE',            'affectation',              'Agent affecté à sa structure.'],
            ['AFFECTE',        'NOMME',              'nomination',               'Agent nommé à son poste.'],
            ['NOMME',          'COMPTE_CREE',        'creation_compte',          'Compte utilisateur créé.'],
            ['COMPTE_CREE',    'PRISE_DE_SERVICE',   'prise_de_service',         'Prise de service enregistrée.'],
            ['PRISE_DE_SERVICE','INTEGRE',           'integration_finalisee',    'Intégration finalisée.'],
        ];

        foreach ($etapes as [$ancien, $nouveau, $action, $commentaire]) {
            HistoriqueIntegration::create([
                'historiable_type' => DossierIntegration::class,
                'historiable_id'   => $dossier->id,
                'utilisateur_id'   => $demandeurId,
                'action'           => $action,
                'ancienne_valeur'  => ['statut' => $ancien],
                'nouvelle_valeur'  => ['statut' => $nouveau],
                'commentaire'      => $commentaire,
            ]);
        }

        return $dossier;
    }

    /**
     * Crée les DocumentDossier pour toutes les pièces obligatoires
     * du TypeIntegration concerné.
     */
    private function creerDocumentsDossier(
        DossierIntegration $dossier,
        Agent $agent,
        int $typeId,
        int $validateurId,
        string $dateValidation,
    ): void {
        $typeIntegration = TypeIntegration::with('documentsObligatoires')->find($typeId);
        if (! $typeIntegration) { return; }

        $slug = strtolower($agent->prenom . '_' . $agent->nom);
        $slug = str_replace([' ', '-', "'"], '_', $slug);
        $slug = strtr($slug, [
            'é'=>'e','è'=>'e','ê'=>'e','à'=>'a','î'=>'i','ô'=>'o','û'=>'u','ç'=>'c',
        ]);
        $basePath = "integration/{$agent->matricule}";

        foreach ($typeIntegration->documentsObligatoires as $typeDoc) {
            $fileName = match(true) {
                str_contains(strtolower($typeDoc->nom), 'curriculum') => "cv_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'diplôme')    => "diplome_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'demande')    => "demande_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'engagement') => "engagement_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'nationalité'),
                str_contains(strtolower($typeDoc->nom), 'nationalite') => "certificat_nationalite_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'casier')     => "casier_judiciaire_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'médical'),
                str_contains(strtolower($typeDoc->nom), 'medical')    => "certificat_medical_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'naissance')  => "acte_naissance_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'convention') => "convention_stage_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'recommandation') => "lettre_recommandation_{$slug}.pdf",
                str_contains(strtolower($typeDoc->nom), 'scolarité'),
                str_contains(strtolower($typeDoc->nom), 'scolarite') => "certificat_scolarite_{$slug}.pdf",
                default => strtolower(str_replace(' ', '_', $typeDoc->nom)) . "_{$slug}.pdf",
            };

            DocumentDossier::firstOrCreate(
                [
                    'dossier_integration_id' => $dossier->id,
                    'type_document_id'        => $typeDoc->id,
                ],
                [
                    'nom_original'   => $fileName,
                    'chemin_fichier' => "{$basePath}/{$fileName}",
                    'est_obligatoire'=> true,
                    'est_valide'     => true,
                    'valide_par'     => $validateurId,
                    'date_validation'=> $dateValidation,
                    'commentaire'    => 'Document reçu et conforme.',
                ]
            );
        }
    }

    /**
     * Crée une Affectation en simulant les étapes :
     *   EN_ATTENTE_VALIDATION → APPROUVEE → ACTIVE
     * avec ValidationWorkflow pour chaque validateur.
     */
    private function creerAffectationAvecWorkflow(
        Agent  $agent,
        string $structurableType,
        int    $structurableId,
        string $dateAffectation,
        array  $validateurs,  // [[userId, dateDecision], ...]
    ): Affectation {
        $affectation = Affectation::firstOrCreate(
            [
                'agent_id'          => $agent->id,
                'structurable_type' => $structurableType,
                'structurable_id'   => $structurableId,
            ],
            [
                'motif'            => 'prise_de_service',
                'date_affectation' => $dateAffectation,
                'statut'           => StatutAffectation::ACTIVE->value,
                'created_by'       => $this->adminId,
            ]
        );

        foreach ($validateurs as $idx => [$userId, $dateDecision]) {
            ValidationWorkflow::firstOrCreate(
                [
                    'validable_type' => Affectation::class,
                    'validable_id'   => $affectation->id,
                    'ordre'          => $idx + 1,
                ],
                [
                    'niveau'        => NiveauValidation::cases()[$idx + 1]->value ?? NiveauValidation::CHEF_SERVICE->value,
                    'validateur_id' => $userId,
                    'statut'        => 'approuve',
                    'commentaire'   => 'Affectation approuvée.',
                    'date_decision' => $dateDecision,
                ]
            );
        }

        // Historique
        foreach ([
            ['en_attente_validation', 'approuvee', 'approbation_affectation', 'Affectation approuvée par la hiérarchie.'],
            ['approuvee',             'active',     'activation_affectation',  'Affectation activée — agent en poste.'],
        ] as [$ancien, $nouveau, $action, $commentaire]) {
            HistoriqueIntegration::create([
                'historiable_type' => Affectation::class,
                'historiable_id'   => $affectation->id,
                'utilisateur_id'   => $this->adminId,
                'action'           => $action,
                'ancienne_valeur'  => ['statut' => $ancien],
                'nouvelle_valeur'  => ['statut' => $nouveau],
                'commentaire'      => $commentaire,
            ]);
        }

        return $affectation;
    }

    /**
     * Crée une Nomination avec workflow complet (postes de responsabilité).
     *   EN_ATTENTE → APPROUVEE → ACTIVE
     */
    private function creerNominationAvecWorkflow(
        Agent  $agent,
        string $poste,
        string $structurableType,
        int    $structurableId,
        string $dateDebut,
        array  $validateurs,  // [[userId, dateDecision], ...]
    ): Nomination {
        $nomination = Nomination::firstOrCreate(
            [
                'agent_id'          => $agent->id,
                'structurable_type' => $structurableType,
                'structurable_id'   => $structurableId,
            ],
            [
                'poste'          => $poste,
                'date_debut'     => $dateDebut,
                'type_acte'      => TypeActeNomination::DECISION->value,
                'statut'         => StatutNomination::ACTIVE->value,
                'soumis_a_essai' => false,
                'created_by'     => $this->adminId,
            ]
        );

        foreach ($validateurs as $idx => [$userId, $dateDecision]) {
            ValidationWorkflow::firstOrCreate(
                [
                    'validable_type' => Nomination::class,
                    'validable_id'   => $nomination->id,
                    'ordre'          => $idx + 1,
                ],
                [
                    'niveau'        => NiveauValidation::cases()[$idx + 2]->value ?? NiveauValidation::DIRECTEUR->value,
                    'validateur_id' => $userId,
                    'statut'        => 'approuve',
                    'commentaire'   => 'Nomination approuvée.',
                    'date_decision' => $dateDecision,
                ]
            );
        }

        foreach ([
            ['en_attente', 'approuvee', 'approbation_nomination', 'Nomination approuvée par la hiérarchie.'],
            ['approuvee',  'active',    'activation_nomination',  'Nomination activée.'],
        ] as [$ancien, $nouveau, $action, $commentaire]) {
            HistoriqueIntegration::create([
                'historiable_type' => Nomination::class,
                'historiable_id'   => $nomination->id,
                'utilisateur_id'   => $this->adminId,
                'action'           => $action,
                'ancienne_valeur'  => ['statut' => $ancien],
                'nouvelle_valeur'  => ['statut' => $nouveau],
                'commentaire'      => $commentaire,
            ]);
        }

        return $nomination;
    }

    /** PriseDeService avec confirmations. */
    private function creerPriseDeService(
        Agent $agent, DossierIntegration $dossier,
        int $responsableId, string $date,
    ): void {
        PriseDeService::firstOrCreate(
            ['agent_id' => $agent->id, 'dossier_integration_id' => $dossier->id],
            [
                'responsable_id'             => $responsableId,
                'date_prise_service'         => $date,
                'confirmation_presence'      => true,
                'confirmation_installation'  => true,
                'confirmation_equipements'   => true,
                'observations'               => 'Prise de service effectuée sans incident.',
            ]
        );
    }

    /** CompteIntegration — liaison Agent ↔ User. */
    private function creerCompteIntegration(Agent $agent, User $user, string $date): void
    {
        CompteIntegration::firstOrCreate(
            ['agent_id' => $agent->id],
            [
                'user_id'                          => $user->id,
                'login'                            => $user->email,
                'email_professionnel'              => $user->email,
                'badge_numero'                     => $agent->matricule,
                'mot_de_passe_provisoire_envoye'   => true,
                'date_creation'                    => $date,
            ]
        );
    }

    // ═════════════════════════════════════════════════════════════════════
    // HELPERS — ENTITÉS DE BASE
    // ═════════════════════════════════════════════════════════════════════

    private function creerInfosPro(
        Agent $agent, string $diplomesSigle, string $specialite, string $etablissement, int $experience,
    ): void {
        $diplome = Diplome::where('sigle', $diplomesSigle)->first();
        InformationsProfessionnelle::firstOrCreate(
            ['agent_id' => $agent->id],
            [
                'diplome_id'         => $diplome?->id,
                'niveau_etude'       => $diplome?->nom ?? $diplomesSigle,
                'specialite'         => $specialite,
                'etablissement'      => $etablissement,
                'annees_experience'  => $experience,
            ]
        );
    }

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

    private function creerUser(Agent $agent, string $role, ?int $bureauId = null): User
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

        if (! $user->agent_id) {
            $user->update(['agent_id' => $agent->id, 'bureau_id' => $bureauId]);
        }

        $user->syncRoles([$role]);

        return $user;
    }

    private function resoudreService(Direction $direction, array $h): Service
    {
        return Service::firstOrCreate(
            ['sigle' => $h['service_sigle'], 'direction_id' => $direction->id],
            ['nom' => $h['service_nom'] ?? $h['service_sigle'], 'sigle' => $h['service_sigle'], 'direction_id' => $direction->id]
        );
    }

    private function resoudreBureau(Service $service, array $h): Bureau
    {
        return Bureau::firstOrCreate(
            ['sigle' => $h['bureau_sigle'], 'service_id' => $service->id],
            ['nom' => $h['bureau_nom'] ?? $h['bureau_sigle'], 'sigle' => $h['bureau_sigle'], 'service_id' => $service->id]
        );
    }

    // ── Helpers email / password ─────────────────────────────────────────
    private function email(string $prenom, string $nom): string
    {
        $n = fn(string $s): string => strtr(strtolower($s), [
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a',
            'î'=>'i','ï'=>'i','ô'=>'o','û'=>'u','ù'=>'u','ü'=>'u','ç'=>'c',
            ' '=>'.', '-'=>'.', "'"=>'',
        ]);
        return $n($prenom) . '.' . $n($nom) . '@artf.cg';
    }

    private function password(string $nom): string
    {
        return ucfirst(strtolower($nom)) . '@2026';
    }
}
