<?php

namespace Database\Seeders;

use App\Models\Classegrillesalariale;
use App\Models\Diplome;
use Illuminate\Database\Seeder;

class DiplomeSeeder extends Seeder
{
    public function run(): void
    {
        // Classes I–II = emplois (annexe 1), pas des diplômes — hors de ce seed.
        $diplomes = [
            ['nom' => 'CEPE', 'sigle' => 'CEPE', 'description' => 'Classe III — Commis', 'classe' => 'Classe III', 'bonification_echelons' => 0],
            ['nom' => 'CAP', 'sigle' => 'CAP', 'description' => 'Classe IV — Commis Principal', 'classe' => 'Classe IV', 'bonification_echelons' => 0],
            ['nom' => 'BEPC', 'sigle' => 'BEPC', 'description' => 'Classe V — Contrôleur', 'classe' => 'Classe V', 'bonification_echelons' => 0],
            ['nom' => 'BET', 'sigle' => 'BET', 'description' => 'Classe V — Contrôleur', 'classe' => 'Classe V', 'bonification_echelons' => 0],
            ['nom' => "Brevet d'Etude Professionnel", 'sigle' => 'BEP', 'description' => 'Classe V — Contrôleur', 'classe' => 'Classe V', 'bonification_echelons' => 1],
            ['nom' => 'Baccalauréat', 'sigle' => 'BAC', 'description' => 'Classe VI — Contrôleur Principal', 'classe' => 'Classe VI', 'bonification_echelons' => 0],
            ['nom' => 'Diplôme Universitaire Technique', 'sigle' => 'DUT', 'description' => 'Classe VII — Vérificateur', 'classe' => 'Classe VII', 'bonification_echelons' => 0],
            ['nom' => 'Brevet de Technicien Supérieur', 'sigle' => 'BTS', 'description' => 'Classe VII — Vérificateur', 'classe' => 'Classe VII', 'bonification_echelons' => 0],
            ['nom' => 'BENAM', 'sigle' => 'BENAM', 'description' => 'Classe VII — Vérificateur', 'classe' => 'Classe VII', 'bonification_echelons' => 0],
            ['nom' => 'Licence', 'sigle' => 'LIC', 'description' => 'Classe VII — Vérificateur', 'classe' => 'Classe VII', 'bonification_echelons' => 1],
            ['nom' => 'Bachelor', 'sigle' => 'BACH', 'description' => 'Classe VII — Vérificateur', 'classe' => 'Classe VII', 'bonification_echelons' => 1],
            ['nom' => 'Maîtrise', 'sigle' => 'MAIT', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 0],
            ['nom' => "Diplôme d'Etude Approfondie", 'sigle' => 'DEA', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 0],
            ['nom' => 'Master', 'sigle' => 'MST', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 1],
            ['nom' => "Diplôme d'Etude Supérieur Spécialisé", 'sigle' => 'DESS', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 1],
            ['nom' => "Diplôme Supérieur de l'ENAM", 'sigle' => 'DSENAM', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 1],
            ['nom' => 'MBA', 'sigle' => 'MBA', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 1],
            ['nom' => 'Diplôme Supérieur Technique Supérieur', 'sigle' => 'DSTS', 'description' => 'Classe VIII — Inspecteur', 'classe' => 'Classe VIII', 'bonification_echelons' => 0],
            ['nom' => 'Doctorat', 'sigle' => 'DOC', 'description' => 'Classe IX — Inspecteur Principal', 'classe' => 'Classe IX', 'bonification_echelons' => 2],
        ];

        $classesParNom = Classegrillesalariale::with('categorie')
            ->get()
            ->keyBy(fn ($c) => $c->categorie->nom ?? '');

        foreach ($diplomes as $data) {
            $classeNom = $data['classe'];
            unset($data['classe']);
            $data['classegrillesalariale_id'] = $classesParNom[$classeNom]->id ?? null;

            Diplome::updateOrCreate(['nom' => $data['nom']], $data);
        }
    }
}
