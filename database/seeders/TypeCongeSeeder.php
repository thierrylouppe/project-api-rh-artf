<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    public function run(): void
    {
        // jours_max = 0 signifie illimité (convention du projet)
        $types = [
            [
                'nom'         => 'Congé annuel',
                'jours_max'   => 30,
                'description' => 'Code : CONG-ANN — Payant — Validation N+1. Congé légal annuel accordé après 11 mois de service continu.',
            ],
            [
                'nom'         => 'Congé de maternité',
                'jours_max'   => 98,
                'description' => 'Code : MATERNITE — Payant — Validation RH. Congé accordé à la mère avant et après l\'accouchement (décret n° 86/067).',
            ],
            [
                'nom'         => 'Congé de paternité',
                'jours_max'   => 10,
                'description' => 'Code : PATERNITE — Payant — Validation RH. Congé accordé au père lors de la naissance d\'un enfant.',
            ],
            [
                'nom'         => 'Congé exceptionnel — décès d\'un parent',
                'jours_max'   => 5,
                'description' => 'Code : EXC-DEC-PAR — Payant — Validation RH. Congé pour décès d\'un parent direct.',
            ],
            [
                'nom'         => 'Congé exceptionnel — mariage',
                'jours_max'   => 5,
                'description' => 'Code : EXC-MARIAGE — Payant — Validation RH. Congé accordé à l\'occasion du mariage de l\'agent.',
            ],
            [
                'nom'         => 'Congé sans solde',
                'jours_max'   => 90,
                'description' => 'Code : SANS-SOLDE — Non payant — Validation DG. Congé accordé sans maintien de la rémunération.',
            ],
            [
                'nom'         => 'Congé maladie',
                'jours_max'   => 0,
                'description' => 'Code : MALADIE — Payant — Validation RH. Congé pour incapacité médicale (durée illimitée sur prescription).',
            ],
            [
                'nom'         => 'Congé sabbatique',
                'jours_max'   => 180,
                'description' => 'Code : SABBATIQUE — Non payant — Validation DG. Congé de longue durée pour projet personnel ou académique.',
            ],
        ];

        foreach ($types as $data) {
            TypeConge::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
