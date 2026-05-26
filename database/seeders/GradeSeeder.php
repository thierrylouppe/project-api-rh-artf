<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['nom' => 'Agent d\'Exécution',            'sigle' => 'AE',   'niveau' => 1],
            ['nom' => 'Agent de Maîtrise',             'sigle' => 'AM',   'niveau' => 2],
            ['nom' => 'Technicien',                    'sigle' => 'TECH', 'niveau' => 3],
            ['nom' => 'Technicien Supérieur',          'sigle' => 'TS',   'niveau' => 4],
            ['nom' => 'Attaché de Direction',          'sigle' => 'AD',   'niveau' => 5],
            ['nom' => 'Chargé d\'Études',              'sigle' => 'CE',   'niveau' => 6],
            ['nom' => 'Conseiller',                    'sigle' => 'CONS', 'niveau' => 7],
            ['nom' => 'Directeur Adjoint',             'sigle' => 'DA',   'niveau' => 8],
            ['nom' => 'Directeur',                     'sigle' => 'DIR',  'niveau' => 9],
            ['nom' => 'Directeur Général Adjoint',     'sigle' => 'DGA',  'niveau' => 10],
            ['nom' => 'Directeur Général',             'sigle' => 'DG',   'niveau' => 11],
        ];

        foreach ($grades as $data) {
            Grade::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
