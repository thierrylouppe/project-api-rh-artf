<?php

namespace Database\Seeders;

use App\Models\QuestionEvaluation;
use Illuminate\Database\Seeder;

/**
 * Grille officielle d'évaluation : 24 critères répartis sur 20 points.
 *
 * Bloc 1 — Compétences professionnelles  : 12 critères → /10
 * Bloc 2 — Assiduité                     :  3 critères → /3
 * Bloc 3 — Relations sociales            :  9 critères → /7
 *                                Total   : 24 critères → /20
 *
 * Source : doc/REFERENTIELS-EVALUATION-NOTATION.md §3
 */
class QuestionEvaluationSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            // --------------------------------------------------------
            // Bloc 1 — Compétences professionnelles (/10)
            // --------------------------------------------------------
            [
                'libelle'      => 'Connaissance technique',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 1,
            ],
            [
                'libelle'      => 'Capacité à anticiper et programmer le travail',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 2,
            ],
            [
                'libelle'      => 'Autonomie et sens de responsabilité',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 3,
            ],
            [
                'libelle'      => 'Capacité à déléguer',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 0.50,
                'ordre'        => 4,
            ],
            [
                'libelle'      => 'Qualité rédactionnelle',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 5,
            ],
            [
                'libelle'      => 'Prise d\'initiatives',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 6,
            ],
            [
                'libelle'      => 'Fiabilité et qualité d\'exécution des tâches',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 7,
            ],
            [
                'libelle'      => 'Respect des délais et sens de l\'organisation',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 8,
            ],
            [
                'libelle'      => 'Rigueur et respect des procédures',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 1.00,
                'ordre'        => 9,
            ],
            [
                'libelle'      => 'Capacité à partager l\'information',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 0.50,
                'ordre'        => 10,
            ],
            [
                'libelle'      => 'Curiosité professionnelle',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 0.50,
                'ordre'        => 11,
            ],
            [
                'libelle'      => 'Capacité à identifier et hiérarchiser les priorités',
                'type_critere' => 'competence_pro',
                'bareme_max'   => 0.50,
                'ordre'        => 12,
            ],

            // --------------------------------------------------------
            // Bloc 2 — Assiduité (/3)
            // --------------------------------------------------------
            [
                'libelle'      => 'Ponctualité',
                'type_critere' => 'assiduite',
                'bareme_max'   => 1.00,
                'ordre'        => 13,
            ],
            [
                'libelle'      => 'Disponibilité',
                'type_critere' => 'assiduite',
                'bareme_max'   => 1.00,
                'ordre'        => 14,
            ],
            [
                'libelle'      => 'Serviabilité',
                'type_critere' => 'assiduite',
                'bareme_max'   => 1.00,
                'ordre'        => 15,
            ],

            // --------------------------------------------------------
            // Bloc 3 — Relations sociales (/7)
            // --------------------------------------------------------
            [
                'libelle'      => 'Capacité à animer et motiver l\'équipe',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 1.00,
                'ordre'        => 16,
            ],
            [
                'libelle'      => 'Adaptabilité',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 0.50,
                'ordre'        => 17,
            ],
            [
                'libelle'      => 'Communication',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 0.50,
                'ordre'        => 18,
            ],
            [
                'libelle'      => 'Rapport avec la hiérarchie',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 1.00,
                'ordre'        => 19,
            ],
            [
                'libelle'      => 'Rapport avec les collègues',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 1.00,
                'ordre'        => 20,
            ],
            [
                'libelle'      => 'Qualité de l\'accueil',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 0.50,
                'ordre'        => 21,
            ],
            [
                'libelle'      => 'Faculté d\'écoute et de réponse',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 0.50,
                'ordre'        => 22,
            ],
            [
                'libelle'      => 'Capacité à travailler en équipe',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 0.50,
                'ordre'        => 23,
            ],
            [
                'libelle'      => 'Respect du code vestimentaire',
                'type_critere' => 'relation_sociale',
                'bareme_max'   => 1.50,
                'ordre'        => 24,
            ],
        ];

        foreach ($questions as $question) {
            QuestionEvaluation::firstOrCreate(
                ['libelle' => $question['libelle'], 'type_critere' => $question['type_critere']],
                array_merge($question, ['actif' => true]),
            );
        }

        $somme = QuestionEvaluation::where('actif', true)->sum('bareme_max');
        $this->command->info(
            sprintf('✅ %d questions d\'évaluation seedées. Somme barèmes : %.2f/20', count($questions), $somme)
        );
    }
}
