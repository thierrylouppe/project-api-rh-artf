<?php

namespace Database\Seeders;

use App\Models\TypeRecrutement;
use Illuminate\Database\Seeder;

class TypeRecrutementSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Valeurs utilisées en base de données
            ['nom' => 'Concours',                   'description' => 'Recrutement par voie de concours ouvert'],
            ['nom' => 'Candidature spontanée',      'description' => 'Dossier déposé à l\'initiative du candidat'],
            ['nom' => 'Mutation',                   'description' => 'Transfert depuis une autre administration ou organisme'],
            ['nom' => 'Recommandation',             'description' => 'Recrutement sur recommandation d\'une autorité'],

            // Valeurs complémentaires — cadre légal FP Congo
            ['nom' => 'Concours professionnel',     'description' => 'Concours réservé aux agents en poste pour promotion'],
            ['nom' => 'Nomination sur titre',       'description' => 'Intégration directe sur présentation du titre ou diplôme'],
            ['nom' => 'Intégration directe',        'description' => 'Intégration par décision hiérarchique sans concours'],
            ['nom' => 'Détachement entrant',        'description' => 'Agent détaché vers l\'administration depuis un autre organisme'],
            ['nom' => 'Reclassement',               'description' => 'Repositionnement d\'un agent suite à réorganisation ou handicap'],
        ];

        foreach ($types as $data) {
            TypeRecrutement::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
