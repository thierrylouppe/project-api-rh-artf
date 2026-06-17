<?php

namespace Database\Seeders;

use App\Models\TypeIntegration;
use Illuminate\Database\Seeder;

class TypeIntegrationSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Recrutement externe',  'description' => 'Nouvel agent recruté par concours ou sélection'],
            ['nom' => 'Mutation',             'description' => 'Agent provenant d\'une autre administration'],
            ['nom' => 'Détachement',          'description' => 'Agent mis temporairement à disposition de l\'ARTF'],
            ['nom' => 'Mise à disposition',   'description' => 'Agent prêté par une autre administration ou institution'],
            ['nom' => 'Réintégration',        'description' => 'Agent revenant après une disponibilité, un détachement ou un congé spécial'],
            ['nom' => 'Contractuel',          'description' => 'Agent recruté sous contrat à durée déterminée ou indéterminée'],
            ['nom' => 'Stage professionnel',  'description' => 'Stagiaire ou agent en période d\'essai'],
        ];

        foreach ($types as $data) {
            TypeIntegration::updateOrCreate(['nom' => $data['nom']], $data);
        }

        TypeIntegration::whereNotIn('nom', array_column($types, 'nom'))->delete();
    }
}
