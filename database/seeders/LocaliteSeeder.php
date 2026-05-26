<?php

namespace Database\Seeders;

use App\Models\Localite;
use Illuminate\Database\Seeder;

class LocaliteSeeder extends Seeder
{
    public function run(): void
    {
        $localites = [
            ['nom' => 'Brazzaville'],
            ['nom' => 'Pointe-Noire'],
            ['nom' => 'Ouesso'],
            ['nom' => 'Dolisie'],
        ];

        foreach ($localites as $data) {
            Localite::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
