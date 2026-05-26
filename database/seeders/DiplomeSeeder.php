<?php

namespace Database\Seeders;

use App\Models\Diplome;
use Illuminate\Database\Seeder;

class DiplomeSeeder extends Seeder
{
    public function run(): void
    {
        $diplomes = [
            ['nom' => 'BEPC',                                     'sigle' => 'BEPC'],
            ['nom' => 'Baccalauréat',                             'sigle' => 'BAC'],
            ['nom' => 'Brevet de Technicien Supérieur',           'sigle' => 'BTS'],
            ['nom' => 'Diplôme Universitaire de Technologie',     'sigle' => 'DUT'],
            ['nom' => 'Licence',                                  'sigle' => 'LIC'],
            ['nom' => 'Licence Professionnelle',                  'sigle' => 'LP'],
            ['nom' => 'Master',                                   'sigle' => 'MST'],
            ['nom' => 'Master Professionnel',                     'sigle' => 'MP'],
            ['nom' => 'Diplôme d\'Ingénieur',                     'sigle' => 'ING'],
            ['nom' => 'Doctorat',                                 'sigle' => 'DOC'],
            ['nom' => 'Certificat de Qualification Professionnelle', 'sigle' => 'CQP'],
        ];

        foreach ($diplomes as $data) {
            Diplome::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
