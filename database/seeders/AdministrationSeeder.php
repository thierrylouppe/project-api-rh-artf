<?php

namespace Database\Seeders;

use App\Models\Administration;
use App\Models\Localite;
use Illuminate\Database\Seeder;

class AdministrationSeeder extends Seeder
{
    public function run(): void
    {
        $brazzaville  = Localite::where('nom', 'Brazzaville')->first();
        $pointeNoire  = Localite::where('nom', 'Pointe-Noire')->first();
        $ouesso       = Localite::where('nom', 'Ouesso')->first();

        $administrations = [
            [
                'nom'        => 'Agence de Regulation des Transferts de Fonds',
                'sigle'      => 'ARFT',
                'localite_id' => $brazzaville->id,
            ],
            [
                'nom'        => 'Direction Générale de la Monnaie et des Relations Financières',
                'sigle'      => 'DGMRF',
                'localite_id' => $pointeNoire->id,
            ],
            [
                'nom'        => 'Agence Nationale des Investigations Financières',
                'sigle'      => 'ANIF',
                'localite_id' => $ouesso->id,
            ],
        ];

        foreach ($administrations as $data) {
            Administration::firstOrCreate(
                ['nom' => $data['nom']],
                $data
            );
        }
    }
}
