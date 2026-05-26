<?php

namespace Database\Seeders;

use App\Models\Echelon;
use Illuminate\Database\Seeder;

class EchelonSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range(1, 12) as $n) {
            Echelon::firstOrCreate(
                ['nom' => "Échelon {$n}"],
                ['nom' => "Échelon {$n}", 'numero' => $n]
            );
        }
    }
}
