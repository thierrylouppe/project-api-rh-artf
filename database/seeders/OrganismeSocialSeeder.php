<?php

namespace Database\Seeders;

use App\Enums\TypeOrganismeSocial;
use App\Models\OrganismeSocial;
use Illuminate\Database\Seeder;

class OrganismeSocialSeeder extends Seeder
{
    public function run(): void
    {
        OrganismeSocial::query()->updateOrCreate(
            ['code' => 'CNSS'],
            [
                'nom' => 'Caisse Nationale de Sécurité Sociale',
                'type' => TypeOrganismeSocial::CNSS,
                'description' => 'Organisme de sécurité sociale de la République du Congo.',
                'actif' => true,
            ]
        );
    }
}
