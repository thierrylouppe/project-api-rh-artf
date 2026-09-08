<?php

namespace Database\Seeders;

use App\Models\PalierAncienneteConge;
use Illuminate\Database\Seeder;

class PalierAncienneteCongeSeeder extends Seeder
{
    public function run(): void
    {
        $paliers = [
            ['anciennete_min' => 0,  'anciennete_max' => 4,    'jours_bonus' => 0],
            ['anciennete_min' => 5,  'anciennete_max' => 9,    'jours_bonus' => 2],
            ['anciennete_min' => 10, 'anciennete_max' => 19,   'jours_bonus' => 4],
            ['anciennete_min' => 20, 'anciennete_max' => null, 'jours_bonus' => 6],
        ];

        foreach ($paliers as $palier) {
            PalierAncienneteConge::firstOrCreate(
                ['anciennete_min' => $palier['anciennete_min']],
                $palier
            );
        }
    }
}
