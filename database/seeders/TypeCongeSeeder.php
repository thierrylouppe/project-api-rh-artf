<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['nom' => 'Congé Annuel',                 'jours_max' => 30, 'description' => 'Congé légal annuel payé'],
            ['nom' => 'Congé Maladie',                'jours_max' => 90, 'description' => 'Congé pour incapacité médicale temporaire'],
            ['nom' => 'Congé Maternité',              'jours_max' => 105,'description' => 'Congé accordé à la mère avant et après l\'accouchement'],
            ['nom' => 'Congé Paternité',              'jours_max' => 10, 'description' => 'Congé accordé au père lors de la naissance d\'un enfant'],
            ['nom' => 'Congé de Formation',           'jours_max' => 0,  'description' => 'Absence pour formation professionnelle agréée'],
            ['nom' => 'Congé Sans Solde',             'jours_max' => 0,  'description' => 'Congé accordé sans maintien de salaire'],
            ['nom' => 'Congé Exceptionnel',           'jours_max' => 5,  'description' => 'Congé pour circonstances exceptionnelles (deuil, mariage…)'],
            ['nom' => 'Congé de Longue Durée',        'jours_max' => 0,  'description' => 'Absence de longue durée pour maladie grave'],
        ];

        foreach ($types as $data) {
            TypeConge::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
