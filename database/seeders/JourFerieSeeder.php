<?php

namespace Database\Seeders;

use App\Models\JourFerie;
use Illuminate\Database\Seeder;

class JourFerieSeeder extends Seeder
{
    public function run(): void
    {
        $jours = [
            ['nom' => 'Jour de l\'an', 'date' => '2026-01-01', 'recurrent' => true],
            ['nom' => 'Fête du Travail', 'date' => '2026-05-01', 'recurrent' => true],
            ['nom' => 'Fête nationale', 'date' => '2026-08-15', 'recurrent' => true],
            ['nom' => 'Toussaint', 'date' => '2026-11-01', 'recurrent' => true],
            ['nom' => 'Noël', 'date' => '2026-12-25', 'recurrent' => true],
        ];

        foreach ($jours as $jour) {
            JourFerie::firstOrCreate(['nom' => $jour['nom'], 'date' => $jour['date']], $jour);
        }
    }
}
