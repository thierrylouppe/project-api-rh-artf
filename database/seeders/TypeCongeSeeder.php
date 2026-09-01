<?php

namespace Database\Seeders;

use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class TypeCongeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'nom'                  => 'Congé annuel',
                'jours_max'            => 30,
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => true,
                'justificatif_requis'  => false,
                'description'          => 'Code : CONG-ANN — Payant — N+1 puis RH. Congé légal annuel.',
            ],
            [
                'nom'                  => 'Congé de maternité',
                'jours_max'            => 98,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : MATERNITE — Payant — Validation RH. Décret n° 86/067.',
            ],
            [
                'nom'                  => 'Congé de paternité',
                'jours_max'            => 10,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : PATERNITE — Payant — Validation RH.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — décès d\'un parent',
                'jours_max'            => 5,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : EXC-DEC-PAR — Payant — Validation RH.',
            ],
            [
                'nom'                  => 'Congé exceptionnel — mariage',
                'jours_max'            => 5,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : EXC-MARIAGE — Payant — Validation RH.',
            ],
            [
                'nom'                  => 'Congé sans solde',
                'jours_max'            => 90,
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => true,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : SANS-SOLDE — Non payant — N+1, RH puis DG.',
            ],
            [
                'nom'                  => 'Congé maladie',
                'jours_max'            => 0,
                'necessite_n1'         => false,
                'necessite_rh'         => true,
                'necessite_dg'         => false,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : MALADIE — Payant — Validation RH + certificat.',
            ],
            [
                'nom'                  => 'Congé sabbatique',
                'jours_max'            => 180,
                'necessite_n1'         => true,
                'necessite_rh'         => true,
                'necessite_dg'         => true,
                'debite_solde'         => false,
                'justificatif_requis'  => true,
                'description'          => 'Code : SABBATIQUE — Non payant — N+1, RH puis DG.',
            ],
        ];

        foreach ($types as $data) {
            TypeConge::updateOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
