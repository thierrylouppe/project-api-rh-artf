<?php

namespace Database\Seeders;

use App\Models\RegleAcquisitionConge;
use App\Models\TypeConge;
use Illuminate\Database\Seeder;

class RegleAcquisitionCongeSeeder extends Seeder
{
    public function run(): void
    {
        $annuel = TypeConge::query()->where('nom', 'Congé annuel')->first();
        if ($annuel === null) {
            return;
        }

        RegleAcquisitionConge::firstOrCreate(
            ['type_conge_id' => $annuel->id],
            ['jours_par_mois' => 2.5, 'jours_max' => 30]
        );
    }
}
