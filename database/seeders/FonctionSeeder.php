<?php

namespace Database\Seeders;

use App\Models\Fonction;
use Illuminate\Database\Seeder;

class FonctionSeeder extends Seeder
{
    public function run(): void
    {
        $fonctions = [
            ['nom' => 'Directeur Général',                    'sigle' => 'DG'],
            ['nom' => 'Directeur Général Adjoint',            'sigle' => 'DGA'],
            ['nom' => 'Directeur',                            'sigle' => 'DIR'],
            ['nom' => 'Directeur Adjoint',                    'sigle' => 'DA'],
            ['nom' => 'Chef de Service',                      'sigle' => 'CS'],
            ['nom' => 'Chef de Bureau',                       'sigle' => 'CB'],
            ['nom' => 'Agent Comptable',                      'sigle' => 'AC'],
            ['nom' => 'Chargé d\'Études',                     'sigle' => 'CE'],
            ['nom' => 'Conseiller Juridique',                 'sigle' => 'CJ'],
            ['nom' => 'Responsable Ressources Humaines',      'sigle' => 'RRH'],
            ['nom' => 'Responsable Informatique',             'sigle' => 'RI'],
            ['nom' => 'Responsable Communication',            'sigle' => 'RC'],
            ['nom' => 'Agent',                                'sigle' => 'AGT'],
            ['nom' => 'Stagiaire',                            'sigle' => 'STG'],
        ];

        foreach ($fonctions as $data) {
            Fonction::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
