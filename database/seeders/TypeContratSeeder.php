<?php

namespace Database\Seeders;

use App\Models\TypeContrat;
use Illuminate\Database\Seeder;

class TypeContratSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Contrat à Durée Indéterminée', 'sigle' => 'CDI', 'description' => 'Contrat permanent sans date de fin'],
            ['nom' => 'Contrat à Durée Déterminée',   'sigle' => 'CDD', 'description' => 'Contrat avec date de fin fixée'],
            ['nom' => 'Stage',                        'sigle' => 'STG', 'description' => 'Convention de stage académique ou professionnel'],
            ['nom' => 'Consultant',                   'sigle' => 'CONS','description' => 'Prestation de conseil à durée limitée'],
        ];

        foreach ($types as $data) {
            TypeContrat::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
