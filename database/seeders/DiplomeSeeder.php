<?php

namespace Database\Seeders;

use App\Models\Diplome;
use Illuminate\Database\Seeder;

class DiplomeSeeder extends Seeder
{
    public function run(): void
    {
        $diplomes = [
            // Classe I — Personnel de service
            ['nom' => 'Agent de sécurité',                         'sigle' => 'AS',     'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Garçon de bureau',                          'sigle' => 'GB',     'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Jardinier',                                  'sigle' => 'JARD',   'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Planton',                                    'sigle' => 'PLAN',   'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Chauffeur',                                  'sigle' => 'CHAUF',  'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Concierge',                                  'sigle' => 'CONC',   'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Mécanicien',                                 'sigle' => 'MECA',   'description' => 'Classe I — Personnel de service'],
            ['nom' => 'Ouvrier qualifié',                           'sigle' => 'OQ',     'description' => 'Classe I — Personnel de service'],

            // Classe II — Personnel de service spécialisé
            ['nom' => 'Chauffeur Mécanicien',                       'sigle' => 'CM',     'description' => 'Classe II — Personnel de service spécialisé'],
            ['nom' => 'Ouvrier spécialisé',                         'sigle' => 'OS',     'description' => 'Classe II — Personnel de service spécialisé'],
            ['nom' => 'Technicien spécialisé',                      'sigle' => 'TS',     'description' => 'Classe II — Personnel de service spécialisé'],
            ['nom' => 'Technicien de surface',                      'sigle' => 'TSURF',  'description' => 'Classe II — Personnel de service spécialisé'],

            // Classe III — Commis
            ['nom' => 'CEPE',                                       'sigle' => 'CEPE',   'description' => 'Classe III — Commis'],

            // Classe IV — Commis Principal
            ['nom' => 'CAP',                                        'sigle' => 'CAP',    'description' => 'Classe IV — Commis Principal'],

            // Classe V — Contrôleur
            ['nom' => 'BEPC',                                       'sigle' => 'BEPC',   'description' => 'Classe V — Contrôleur'],
            ['nom' => 'BET',                                        'sigle' => 'BET',    'description' => 'Classe V — Contrôleur'],
            ['nom' => "Brevet d'Etude Professionnel",               'sigle' => 'BEP',    'description' => 'Classe V — Contrôleur'],

            // Classe VI — Contrôleur Principal
            ['nom' => 'Baccalauréat',                               'sigle' => 'BAC',    'description' => 'Classe VI — Contrôleur Principal'],

            // Classe VII — Vérificateur
            ['nom' => 'Diplôme Universitaire Technique',            'sigle' => 'DUT',    'description' => 'Classe VII — Vérificateur'],
            ['nom' => 'Brevet de Technicien Supérieur',             'sigle' => 'BTS',    'description' => 'Classe VII — Vérificateur'],
            ['nom' => 'BENAM',                                      'sigle' => 'BENAM',  'description' => 'Classe VII — Vérificateur'],
            ['nom' => 'Licence',                                    'sigle' => 'LIC',    'description' => 'Classe VII — Vérificateur'],

            // Classe VIII — Inspecteur
            ['nom' => "Diplôme d'Etude Approfondie",                'sigle' => 'DEA',    'description' => 'Classe VIII — Inspecteur'],
            ['nom' => 'Master',                                     'sigle' => 'MST',    'description' => 'Classe VIII — Inspecteur'],
            ['nom' => "Diplôme d'Etude Supérieur Spécialisé",       'sigle' => 'DESS',   'description' => 'Classe VIII — Inspecteur'],
            ['nom' => "Diplôme Supérieur de l'ENAM",                'sigle' => 'DSENAM', 'description' => 'Classe VIII — Inspecteur'],
            ['nom' => 'MBA',                                        'sigle' => 'MBA',    'description' => 'Classe VIII — Inspecteur'],
            ['nom' => 'Diplôme Supérieur Technique Supérieur',      'sigle' => 'DSTS',   'description' => 'Classe VIII — Inspecteur'],

            // Classe IX — Inspecteur Principal
            ['nom' => 'Doctorat',                                   'sigle' => 'DOC',    'description' => 'Classe IX — Inspecteur Principal'],
        ];

        foreach ($diplomes as $data) {
            Diplome::firstOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
