<?php

namespace Database\Seeders;

use App\Models\Administration;
use App\Models\Direction;
use Illuminate\Database\Seeder;

class DirectionSeeder extends Seeder
{
    public function run(): void
    {
        $arft = Administration::where('sigle', 'ARFT')->first();

        $directions = [
            ['nom' => 'DIRECTION GENERALE',                                                  'sigle' => 'D.G'],
            ['nom' => 'DIRECTION FINANCIÈRE',                                                'sigle' => 'D.F'],
            ['nom' => 'DIRECTION DE LA REGULATION',                                          'sigle' => 'D.R'],
            ['nom' => 'DIRECTION DES RESSOURCES HUMAINES ET DE LA LOGISTIQUE',               'sigle' => 'D.R.H.L'],
            ['nom' => 'AGENCE COMPTABLE',                                                    'sigle' => 'A.C'],
            ['nom' => "DIRECTION DE L'INSPECTION DES STAT. ET DES ÉTUDES",                   'sigle' => 'D.I.S.E'],
            ['nom' => "DIRECTION DES AFF. JURIDIQUES, DES INVEST. ET DE LA COOP.",           'sigle' => 'D.A.J.I.C'],
            ['nom' => 'DIRECTION DEPARTEMENTALE POINTE-NOIRE',                               'sigle' => 'D.D.P.N'],
            ['nom' => 'DIRECTION DEPARTEMENTALE OUESSO',                                     'sigle' => 'D.D.O'],
            ['nom' => 'DIRECTION DEPARTEMENTALE DOLISIE',                                    'sigle' => 'D.D.D'],
        ];

        foreach ($directions as $data) {
            Direction::firstOrCreate(
                ['nom' => $data['nom'], 'administration_id' => $arft->id],
                array_merge($data, ['administration_id' => $arft->id])
            );
        }
    }
}
