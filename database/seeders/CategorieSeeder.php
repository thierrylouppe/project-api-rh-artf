<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Classe I',   'sigle' => 'CL-I',    'description' => 'Personnel de service'],
            ['nom' => 'Classe II',  'sigle' => 'CL-II',   'description' => 'Personnel de service spécialisé'],
            ['nom' => 'Classe III', 'sigle' => 'CL-III',  'description' => 'Commis'],
            ['nom' => 'Classe IV',  'sigle' => 'CL-IV',   'description' => 'Commis Principal'],
            ['nom' => 'Classe V',   'sigle' => 'CL-V',    'description' => 'Contrôleur'],
            ['nom' => 'Classe VI',  'sigle' => 'CL-VI',   'description' => 'Contrôleur Principal'],
            ['nom' => 'Classe VII', 'sigle' => 'CL-VII',  'description' => 'Vérificateur'],
            ['nom' => 'Classe VIII','sigle' => 'CL-VIII', 'description' => 'Inspecteur'],
            ['nom' => 'Classe IX',  'sigle' => 'CL-IX',   'description' => 'Inspecteur Principal'],
            ['nom' => 'Classe X',   'sigle' => 'CL-X',    'description' => 'Hors Classe'],
        ];

        foreach ($categories as $data) {
            Categorie::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
