<?php

namespace Database\Seeders;

use App\Models\ParametreApplication;
use Illuminate\Database\Seeder;

class ParametreApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $parametres = [
            ['cle' => 'app_name',        'valeur' => 'Gestion RH ARFT',           'description' => 'Nom de l\'application'],
            ['cle' => 'app_version',     'valeur' => '1.0.0',                     'description' => 'Version de l\'application'],
            ['cle' => 'org_name',        'valeur' => 'Agence de Regulation des Transferts de Fonds', 'description' => 'Organisation'],
            ['cle' => 'org_sigle',       'valeur' => 'ARFT',                      'description' => 'Sigle organisation'],
            ['cle' => 'pays',            'valeur' => 'République du Congo',       'description' => 'Pays'],
            ['cle' => 'devise',          'valeur' => 'XAF',                       'description' => 'Devise'],
        ];

        foreach ($parametres as $data) {
            ParametreApplication::firstOrCreate(['cle' => $data['cle']], $data);
        }
    }
}
