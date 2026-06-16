<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class ClassegrillesalarialeSeeder extends Seeder
{
    /** Mapping classe → grade → coefficient (barème FP Congo). */
    private const CLASSES = [
        ['classe' => 'Classe I',    'grade' => 'Personnel de service',          'coefficient' => 45],
        ['classe' => 'Classe II',   'grade' => 'Personnel de service spécialisé','coefficient' => 50],
        ['classe' => 'Classe III',  'grade' => 'Commis',                         'coefficient' => 55],
        ['classe' => 'Classe IV',   'grade' => 'Commis Principal',               'coefficient' => 60],
        ['classe' => 'Classe V',    'grade' => 'Contrôleur',                     'coefficient' => 75],
        ['classe' => 'Classe VI',   'grade' => 'Contrôleur Principal',           'coefficient' => 90],
        ['classe' => 'Classe VII',  'grade' => 'Vérificateur',                   'coefficient' => 105],
        ['classe' => 'Classe VIII', 'grade' => 'Inspecteur',                     'coefficient' => 120],
        ['classe' => 'Classe IX',   'grade' => 'Inspecteur Principal',           'coefficient' => 145],
        ['classe' => 'Classe X',    'grade' => 'Hors Classe',                    'coefficient' => 170],
    ];

    public function run(): void
    {
        // Pré-charger les référentiels pour éviter N+1 queries
        $categories = Categorie::whereIn('nom', array_column(self::CLASSES, 'classe'))
            ->pluck('id', 'nom');

        $grades = Grade::whereIn('nom', array_column(self::CLASSES, 'grade'))
            ->pluck('id', 'nom');

        foreach (self::CLASSES as $entry) {
            $categorieId = $categories[$entry['classe']] ?? null;
            $gradeId     = $grades[$entry['grade']]     ?? null;

            if (! $categorieId || ! $gradeId) {
                $this->command->warn("Référentiel manquant : {$entry['classe']} / {$entry['grade']}");
                continue;
            }

            Classegrillesalariale::firstOrCreate(
                ['categorie_id' => $categorieId],
                [
                    'grade_id'    => $gradeId,
                    'coefficient' => $entry['coefficient'],
                ]
            );
        }
    }
}
