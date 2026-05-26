<?php

namespace Database\Seeders;

use App\Models\Bureau;
use App\Models\Service;
use Illuminate\Database\Seeder;

class BureauSeeder extends Seeder
{
    public function run(): void
    {
        $svcs = Service::pluck('id', 'sigle');

        $bureaux = [
            // S.S.I — Service Système d'Information
            ['nom' => 'BUREAU EXPLOITATION',                                'sigle' => 'B.EXP',    'service' => 'S.S.I'],
            ['nom' => 'BUREAU SYSTEME ET RÉSEAUX',                          'sigle' => 'B.R',      'service' => 'S.S.I'],
            ['nom' => 'BUREAU DÉVELOPPEMENT',                               'sigle' => 'B.D.B.D.', 'service' => 'S.S.I'],
            ['nom' => 'BUREAU MAINTENANCE',                                 'sigle' => 'B.MAINT',  'service' => 'S.S.I'],

            // S.R.H — Service des Ressources Humaines
            ['nom' => 'BUREAU FORMATION',                                   'sigle' => 'B.F',      'service' => 'S.R.H'],
            ['nom' => 'BUREAU PERSONNEL',                                   'sigle' => 'B.P',      'service' => 'S.R.H'],
            ['nom' => 'BUREAU SOLDE',                                       'sigle' => 'B.S.',     'service' => 'S.R.H'],

            // S.B — Service Budget
            ['nom' => 'BUREAU DE LA RECETTE',                               'sigle' => 'B.RCT',    'service' => 'S.B'],
            ['nom' => 'BUREAU DE LA DEPENSE',                               'sigle' => 'B.DEP',    'service' => 'S.B'],
            ['nom' => 'BUREAU DES ETUDES DE POLITIQUE BUDGETAIRES ET FINANCIERES', 'sigle' => 'B.E.P.B.F', 'service' => 'S.B'],

            // S.D.G — Secrétariat Direction Générale
            ['nom' => 'BUREAU RECEPTION ET COURRIER',                       'sigle' => 'B.R.C',    'service' => 'S.D.G'],

            // S.L.T.C.A — Service Législation du Travail et Conformité Adm.
            ['nom' => 'BUREAU ETUDE ET PLANIFICATION',                      'sigle' => 'B.PL',     'service' => 'S.L.T.C.A'],

            // S.A.R — Service des Agréments et de la Régulation
            ['nom' => 'BUREAU DES OPERATIONS DE MOBILE MONEY ET ASSIMILES', 'sigle' => 'B.O.M.M.A', 'service' => 'S.A.R'],
            ['nom' => 'BUREAU ETABLISSEMENT CRÉDIT',                        'sigle' => 'B.ETS.C',  'service' => 'S.A.R'],

            // S.D — Service Dépense
            ['nom' => 'BUREAU RECOUVREMENT',                                'sigle' => 'B.REC',    'service' => 'S.D'],
            ['nom' => 'BUREAU CONTRÔLE ET DÉPENSE',                         'sigle' => 'B.C.DEP.', 'service' => 'S.D'],

            // S.O — Service Ordonnancement
            ['nom' => 'BUREAU DES OPERATIONS AUPRES DE LA DGB',             'sigle' => 'B.O.DGB',  'service' => 'S.O'],
            ['nom' => 'BUREAU DES OPERATIONS AUPRES DE LA DGT',             'sigle' => 'B.O.DGT',  'service' => 'S.O'],

            // S.I — Service de l'Inspection
            ['nom' => 'BUREAU CONTROLE ET ENQUETES',                        'sigle' => 'B.C.E',    'service' => 'S.I'],
            ['nom' => 'BUREAU EVALUATIONS ET ANALYSES',                     'sigle' => 'B.E.A',    'service' => 'S.I'],
            ['nom' => "BUREAU DE L'INTELLIGENCE ECONOMIQUE",                'sigle' => 'B.I.E',    'service' => 'S.I'],

            // S.S.A — Service des Statistiques et Analyses
            ['nom' => 'BUREAU DE COLLECTE DE DONNEES',                      'sigle' => 'B.C.D',    'service' => 'S.S.A'],
            ['nom' => 'BUREAU PREVISION ET BASES DE DONNEES',               'sigle' => 'B.P.B.D',  'service' => 'S.S.A'],
            ['nom' => 'BUREAU DES COMPTES EXTERIEURS',                      'sigle' => 'B.C.EXT',  'service' => 'S.S.A'],

            // S.E — Service des Études
            ['nom' => 'BUREAU DES ETUDES GENERALES',                        'sigle' => 'B.E.G',    'service' => 'S.E'],
            ['nom' => "BUREAU DES RAPPORT D'ACTIVITES",                     'sigle' => 'B.R.A',    'service' => 'S.E'],

            // S.T.C — Service des Transactions Courantes
            ['nom' => 'BUREAU FINANCES EXTERIEURS',                         'sigle' => 'B.F.E',    'service' => 'S.T.C'],
            ['nom' => 'BUREAU CONTROLE DE CONFORMITE',                      'sigle' => 'B.C.C',    'service' => 'S.T.C'],
            ['nom' => 'BUREAU DES PRETS, DES EMPRUNTS ET DES TITRES',       'sigle' => 'B.P.E.T',  'service' => 'S.T.C'],
            ['nom' => 'BUREAU DES OPERATEURS DE TRANSFERTS CLASSIQUES',     'sigle' => 'B.O.T.C',  'service' => 'S.T.C'],

            // S.COMM — Service Communication
            ['nom' => 'BUREAU COMMUNICATION INTERNE',                       'sigle' => 'B.C.I.',   'service' => 'S.COMM'],
            ['nom' => 'BUREAU COMMUNICATION EXTERNE',                       'sigle' => 'B.C.E.',   'service' => 'S.COMM'],

            // S.LOG — Service Logistique
            ['nom' => 'BUREAU ECONOMAT',                                    'sigle' => 'B.ECON',   'service' => 'S.LOG'],
            ['nom' => 'BUREAU ÉQUIPEMENT',                                  'sigle' => 'B.EQ',     'service' => 'S.LOG'],
            ['nom' => 'BUREAU MOBILIER DE BUREAU',                          'sigle' => 'B.M.B',    'service' => 'S.LOG'],
        ];

        foreach ($bureaux as $data) {
            $serviceId = $svcs[$data['service']] ?? null;
            if (! $serviceId) {
                continue;
            }

            Bureau::firstOrCreate(
                ['nom' => $data['nom'], 'service_id' => $serviceId],
                ['nom' => $data['nom'], 'sigle' => $data['sigle'], 'service_id' => $serviceId]
            );
        }
    }
}
