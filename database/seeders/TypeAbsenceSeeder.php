<?php

namespace Database\Seeders;

use App\Models\TypeAbsence;
use Illuminate\Database\Seeder;

class TypeAbsenceSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Maladie',                      'justification_requise' => true,  'description' => 'Absence pour raison médicale'],
            ['nom' => 'Accident de Travail',           'justification_requise' => true,  'description' => 'Absence suite à un accident survenu au travail'],
            ['nom' => 'Absence Non Justifiée',         'justification_requise' => false, 'description' => 'Absence sans motif déclaré'],
            ['nom' => 'Événement Familial',            'justification_requise' => true,  'description' => 'Décès, mariage ou naissance'],
            ['nom' => 'Convocation Officielle',        'justification_requise' => true,  'description' => 'Convocation administrative ou judiciaire'],
            ['nom' => 'Retard',                        'justification_requise' => false, 'description' => 'Arrivée tardive au poste de travail'],
            ['nom' => 'Autorisation d\'Absence',       'justification_requise' => true,  'description' => 'Absence autorisée par la hiérarchie'],
        ];

        foreach ($types as $data) {
            TypeAbsence::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
