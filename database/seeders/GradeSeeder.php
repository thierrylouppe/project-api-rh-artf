<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['nom' => 'Personnel de service',          'sigle' => 'PS',   'niveau' => 1,  'description' => 'Coefficient de base : 45'],
            ['nom' => 'Personnel de service spécialisé','sigle' => 'PSS',  'niveau' => 2,  'description' => 'Coefficient de base : 50'],
            ['nom' => 'Commis',                         'sigle' => 'COM',  'niveau' => 3,  'description' => 'Coefficient de base : 55'],
            ['nom' => 'Commis Principal',               'sigle' => 'CP',   'niveau' => 4,  'description' => 'Coefficient de base : 60'],
            ['nom' => 'Contrôleur',                     'sigle' => 'CTR',  'niveau' => 5,  'description' => 'Coefficient de base : 75'],
            ['nom' => 'Contrôleur Principal',           'sigle' => 'CTRP', 'niveau' => 6,  'description' => 'Coefficient de base : 90'],
            ['nom' => 'Vérificateur',                   'sigle' => 'VER',  'niveau' => 7,  'description' => 'Coefficient de base : 105'],
            ['nom' => 'Inspecteur',                     'sigle' => 'INS',  'niveau' => 8,  'description' => 'Coefficient de base : 120'],
            ['nom' => 'Inspecteur Principal',           'sigle' => 'INSP', 'niveau' => 9,  'description' => 'Coefficient de base : 145'],
            ['nom' => 'Hors Classe',                    'sigle' => 'HC',   'niveau' => 10, 'description' => 'Coefficient de base : 170'],
        ];

        foreach ($grades as $data) {
            Grade::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
