<?php

namespace Database\Seeders;

use App\Models\PalierAncienneteConge;
use Illuminate\Database\Seeder;

class PalierAncienneteCongeSeeder extends Seeder
{
    public function run(): void
    {
        // Paliers définis par la Convention Collective ARTF — Art. 77 (congés annuels)
        // Les jours bonus s'ajoutent à la base de 30 jours ouvrables
        $paliers = [
            ['anciennete_min' => 0,  'anciennete_max' => 4,  'jours_bonus' => 0],
            ['anciennete_min' => 5,  'anciennete_max' => 9,  'jours_bonus' => 6],
            ['anciennete_min' => 10, 'anciennete_max' => 14, 'jours_bonus' => 8],
            ['anciennete_min' => 15, 'anciennete_max' => 19, 'jours_bonus' => 10],
            ['anciennete_min' => 20, 'anciennete_max' => 24, 'jours_bonus' => 12],
            ['anciennete_min' => 25, 'anciennete_max' => 29, 'jours_bonus' => 14],
            ['anciennete_min' => 30, 'anciennete_max' => 34, 'jours_bonus' => 16],
            ['anciennete_min' => 35, 'anciennete_max' => null, 'jours_bonus' => 18],
        ];

        foreach ($paliers as $palier) {
            PalierAncienneteConge::updateOrCreate(
                ['anciennete_min' => $palier['anciennete_min']],
                $palier
            );
        }
    }
}
