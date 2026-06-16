<?php

namespace Database\Seeders;

use App\Models\TypeAbsence;
use Illuminate\Database\Seeder;

class TypeAbsenceSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Absence non justifiée',      'justification_requise' => false, 'description' => 'Absence non déclarée ou sans justificatif valable'],
            ['nom' => 'Permission d\'absence',      'justification_requise' => true,  'description' => 'Courte absence autorisée préalablement par la hiérarchie'],
            ['nom' => 'Absence pour maladie',       'justification_requise' => true,  'description' => 'Absence médicale avec certificat du médecin traitant'],
            ['nom' => 'Absence pour formation',     'justification_requise' => true,  'description' => 'Participation à un stage ou séminaire professionnel'],
            ['nom' => 'Absence pour mission',       'justification_requise' => true,  'description' => 'Déplacement de service ou mission officielle'],
            ['nom' => 'Absence syndicale',          'justification_requise' => true,  'description' => 'Activité syndicale autorisée par la direction'],
            ['nom' => 'Retard',                     'justification_requise' => false, 'description' => 'Arrivée tardive au poste de travail (absence partielle)'],
            ['nom' => 'Mise en disponibilité',      'justification_requise' => true,  'description' => 'Suspension temporaire des obligations de service'],
        ];

        foreach ($types as $data) {
            TypeAbsence::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
