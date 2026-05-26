<?php

namespace Database\Seeders;

use App\Models\TypeRecrutement;
use Illuminate\Database\Seeder;

class TypeRecrutementSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Concours',              'description' => 'Recrutement par voie de concours'],
            ['nom' => 'Appel à Candidature',   'description' => 'Recrutement sur dossier et entretien'],
            ['nom' => 'Mutation',              'description' => 'Transfert d\'un autre organisme'],
            ['nom' => 'Détachement',           'description' => 'Agent détaché d\'une autre administration'],
            ['nom' => 'Mise à Disposition',    'description' => 'Agent mis à disposition par une autre entité'],
            ['nom' => 'Intégration Directe',   'description' => 'Intégration par décision hiérarchique'],
            ['nom' => 'Réintégration',         'description' => 'Retour d\'un agent après interruption de service'],
        ];

        foreach ($types as $data) {
            TypeRecrutement::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
