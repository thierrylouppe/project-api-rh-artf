<?php

namespace Database\Seeders;

use App\Models\Parametregrille;
use Illuminate\Database\Seeder;

class ParametregrileSeeder extends Seeder
{
    public function run(): void
    {
        Parametregrille::firstOrCreate([], [
            'valeur_point_indice' => 300,
            'indice_base'         => 445,
            'echelon_depart'      => 1,
            'echelon_fin'         => 12,
            'ecart_depart'        => 45,
        ]);
    }
}
