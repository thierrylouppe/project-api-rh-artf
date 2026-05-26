<?php

namespace Database\Seeders;

use App\Models\Direction;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        // Alias courts → nom complet de la direction parente
        $dirs = Direction::pluck('id', 'sigle');

        $services = [
            // Direction Générale (D.G)
            ['nom' => "SERVICE SYSTEME D'INFORMATION",               'sigle' => 'S.S.I',    'direction' => 'D.G'],
            ['nom' => 'SÉCRETARIAT DIRECTION GÉNÉRALE',              'sigle' => 'S.D.G',    'direction' => 'D.G'],
            ['nom' => 'SERVICE COMMUNICATION',                       'sigle' => 'S.COMM',   'direction' => 'D.G'],
            ['nom' => 'SERVICE AUDIT INTERNE',                       'sigle' => 'S.AUD',    'direction' => 'D.G'],

            // Direction Financière (D.F)
            ['nom' => 'SERVICE BUDGET',                              'sigle' => 'S.B',      'direction' => 'D.F'],
            ['nom' => 'SERVICE ORDONNANCEMENT',                      'sigle' => 'S.O',      'direction' => 'D.F'],

            // Direction de la Régulation (D.R)
            ['nom' => 'SERVICE DES AGREMENTS ET DE LA REGULATION',   'sigle' => 'S.A.R',    'direction' => 'D.R'],
            ['nom' => 'SERVICE INVESTISSEMENTS ET CAPITAUX',         'sigle' => 'S.I.C',    'direction' => 'D.R'],
            ['nom' => "SERVICE DES OPERATION EN CAPITAL",            'sigle' => 'S.O.C',    'direction' => 'D.R'],
            ['nom' => 'SERVICE DES TRANSACTIONS COURANTES',          'sigle' => 'S.T.C',    'direction' => 'D.R'],

            // Direction RH et Logistique (D.R.H.L)
            ['nom' => 'SERVICE DES RESSOURCES HUMAINES',             'sigle' => 'S.R.H',    'direction' => 'D.R.H.L'],
            ['nom' => "SERVICE LEGISLATION DU TRAVAIL ET CONFORMITÉ ADM.", 'sigle' => 'S.L.T.C.A', 'direction' => 'D.R.H.L'],
            ['nom' => 'SERVICE LOGISTIQUE',                          'sigle' => 'S.LOG',    'direction' => 'D.R.H.L'],

            // Agence Comptable (A.C)
            ['nom' => 'SERVICE RECETTE',                             'sigle' => 'S.R',      'direction' => 'A.C'],
            ['nom' => 'SERVICE DEPENSE',                             'sigle' => 'S.D',      'direction' => 'A.C'],
            ['nom' => 'SERVICE FONDS ET VALEURS',                    'sigle' => 'S.F.V',    'direction' => 'A.C'],

            // Direction Inspection, Stats et Études (D.I.S.E)
            ['nom' => "SERVICE DES ÉTUDES",                          'sigle' => 'S.E',      'direction' => 'D.I.S.E'],
            ['nom' => "SERVICE DE L'INSPECTION",                     'sigle' => 'S.I',      'direction' => 'D.I.S.E'],
            ['nom' => 'SERVICE DES STATISTIQUES ET ANALYSES',        'sigle' => 'S.S.A',    'direction' => 'D.I.S.E'],

            // Direction Aff. Juridiques, Investissements et Coop. (D.A.J.I.C)
            ['nom' => 'SERVICE DES AFFAIRES JURIDIQUES',             'sigle' => 'S.A.J',    'direction' => 'D.A.J.I.C'],
            ['nom' => 'SERVICE CONTENTIEUX ET POURSUITES',           'sigle' => 'S.C.P',    'direction' => 'D.A.J.I.C'],
        ];

        foreach ($services as $data) {
            $directionId = $dirs[$data['direction']] ?? null;
            if (! $directionId) {
                continue;
            }

            Service::firstOrCreate(
                ['nom' => $data['nom'], 'direction_id' => $directionId],
                ['nom' => $data['nom'], 'sigle' => $data['sigle'], 'direction_id' => $directionId]
            );
        }
    }
}
