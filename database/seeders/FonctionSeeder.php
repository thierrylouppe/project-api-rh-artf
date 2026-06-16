<?php

namespace Database\Seeders;

use App\Models\Fonction;
use Illuminate\Database\Seeder;

class FonctionSeeder extends Seeder
{
    public function run(): void
    {
        $fonctions = [
            ['nom' => 'Directeur Général',          'sigle' => 'DG',   'description' => 'Direction générale de l\'organisation'],
            ['nom' => 'Directeur Central',           'sigle' => 'DC',   'description' => 'Direction d\'une direction centrale'],
            ['nom' => 'Directeur Départemental',     'sigle' => 'DD',   'description' => 'Direction d\'une direction départementale'],
            ['nom' => 'Chef de service rattaché',    'sigle' => 'CSR',  'description' => 'Responsable d\'un service rattaché à la direction générale'],
            ['nom' => 'Chef de service',             'sigle' => 'CS',   'description' => 'Responsable d\'un service'],
            ['nom' => 'Chef de bureau',              'sigle' => 'CB',   'description' => 'Responsable d\'un bureau'],
            ['nom' => 'Agent',                       'sigle' => 'AGT',  'description' => 'Agent d\'exécution'],
            ['nom' => 'Stagiaire',                   'sigle' => 'STG',  'description' => 'Agent en période de stage'],
        ];

        foreach ($fonctions as $data) {
            Fonction::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
