<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nom' => 'Catégorie A',  'sigle' => 'CAT-A', 'description' => 'Cadres supérieurs'],
            ['nom' => 'Catégorie B',  'sigle' => 'CAT-B', 'description' => 'Cadres moyens'],
            ['nom' => 'Catégorie C',  'sigle' => 'CAT-C', 'description' => 'Agents d\'exécution qualifiés'],
            ['nom' => 'Catégorie D',  'sigle' => 'CAT-D', 'description' => 'Agents d\'exécution'],
            ['nom' => 'Contractuel',  'sigle' => 'CONT',  'description' => 'Agents sous contrat à durée déterminée'],
            ['nom' => 'Stagiaire',    'sigle' => 'STG',   'description' => 'Agents en période de stage'],
        ];

        foreach ($categories as $data) {
            Categorie::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
