<?php

namespace Tests\Feature;

use Tests\TestCase;

class FicheEvaluationPdfViewTest extends TestCase
{
    public function test_la_fiche_reprend_la_mise_en_forme_validee_et_les_baremes_actuels(): void
    {
        $html = view('pdf.fiche-evaluation', [
            'doc' => $this->document(),
        ])->render();

        $this->assertStringContainsString('Ubuntu-Regular.ttf', $html);
        $this->assertStringContainsString('pdf/img/logo-symbole.png', $html);
        $this->assertStringContainsString('pdf/img/filigrane.png', $html);
        $this->assertStringContainsString('ET DES FINANCES', $html);
        $this->assertStringContainsString('AGENCE DE RÉGULATION', $html);
        $this->assertStringContainsString('BUREAU PERSONNEL', $html);
        $this->assertStringContainsString(config('artf.email'), $html);
        $this->assertStringContainsString('charte-barre', $html);
        $this->assertStringContainsString('RENSEIGNEMENTS GENERAUX', $html);
        $this->assertStringContainsString('Identification de l\'agent à noter', $html);
        $this->assertStringContainsString('Suivi administratif', $html);
        $this->assertStringContainsString('>4<', $html);
        $this->assertStringContainsString('Avertissement', $html);
        $this->assertStringContainsString('CRITERES GENERAUX D\'EVALUATION', $html);
        $this->assertStringContainsString('Compétences professionnelles et techniques', $html);
        $this->assertStringContainsString('Assiduité au travail', $html);
        $this->assertStringContainsString('Relations sociales', $html);
        $this->assertStringContainsString('7,5 / 10', $html);
        $this->assertStringContainsString('2 / 3', $html);
        $this->assertStringContainsString('5 / 7', $html);
        $this->assertStringContainsString('NOTE GLOBALE : 14,5 / 20', $html);
        $this->assertStringContainsString('Signature de l\'évaluateur', $html);
        $this->assertStringContainsString('Signature de l\'évalué(e)', $html);
        $this->assertStringContainsString('class="gras droite">Date de l\'évaluation : 05/10/2026', $html);
        $this->assertStringNotContainsString('Le supérieur hiérarchique', $html);
        $this->assertStringContainsString('>Question</th>', $html);
        $this->assertStringContainsString('EVALUATION GENERALE PAR LA COMMISSION', $html);
        $this->assertStringContainsString('Total général', $html);
        $this->assertMatchesRegularExpression(
            '/Total général<\/td>\s*<td class="centre gras">14,5<\/td>\s*<td class="centre gras">\/ 20<\/td>/',
            $html
        );
        $this->assertStringContainsString('nouvelle-page centre souligne">Evaluation du Président de la commission', $html);
        $this->assertStringContainsString('DECISION DE LA COMMISSION D\'AVANCEMENT', $html);
        $this->assertMatchesRegularExpression(
            '/<\/div>\s*<p class="droite">Fait à Brazzaville, le<\/p>\s*<p class="droite gras">LE PRESIDENT\.<\/p>/',
            $html
        );
        $this->assertStringContainsString('<h3>1. <span class="souligne">Identification de l\'agent à noter</span></h3>', $html);
        $this->assertStringContainsString('<table class="identite">', $html);
        $this->assertStringContainsString('<h3>2. <span class="souligne">Identification du supérieur hiérarchique</span></h3>', $html);
    }

    public function test_la_fiche_indique_aucune_sanction_quand_il_n_y_en_a_pas(): void
    {
        $doc = $this->document();
        $doc['sanctions'] = [];
        $doc['commission'] = null;
        $doc['reclamation'] = null;
        $doc['connaissances'] = [];

        $html = view('pdf.fiche-evaluation', ['doc' => $doc])->render();

        $this->assertStringContainsString('Aucune sanction', $html);
        $this->assertStringNotContainsString('DECISION DE LA COMMISSION', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private function document(): array
    {
        return [
            'titre' => "FICHE INDIVIDUELLE D'EVALUATION",
            'session' => 'Session du 01/01/2026 au 30/06/2026',
            'date_evaluation' => '05/10/2026',
            'logo' => null,
            'photo' => null,
            'agent' => [
                'nom' => 'MOUANDA',
                'prenom' => 'Grace',
                'matricule' => 'A-100',
                'grade' => 'Catégorie II',
                'fonction' => 'Chargée d\'études',
                'anciennete' => '3 ans 2 mois',
                'direction' => 'Direction des ressources humaines',
                'service' => 'SRH',
                'bureau' => 'BP',
            ],
            'superieur' => [
                'nom_complet' => 'OKEMBA Paul',
                'fonction_grade' => 'Chef de service / Catégorie III',
            ],
            'jours_absence' => 4,
            'sanctions' => [
                ['nature' => 'Avertissement', 'nombre' => 1],
            ],
            'blocs' => [
                [
                    'numero' => '1',
                    'titre' => 'Compétences professionnelles et techniques',
                    'saut' => false,
                    'lignes' => [
                        ['libelle' => 'Connaissance technique', 'note' => '1', 'bareme' => '0 à 1'],
                    ],
                    'total' => '7,5',
                    'bareme' => '10',
                ],
                [
                    'numero' => '2',
                    'titre' => 'Assiduité au travail',
                    'saut' => true,
                    'lignes' => [
                        ['libelle' => 'Ponctualité', 'note' => '1', 'bareme' => '0 à 1'],
                    ],
                    'total' => '2',
                    'bareme' => '3',
                ],
                [
                    'numero' => '3',
                    'titre' => 'Relations sociales',
                    'saut' => false,
                    'lignes' => [
                        ['libelle' => 'Communication', 'note' => '0,5', 'bareme' => '0 à 0,5'],
                    ],
                    'total' => '5',
                    'bareme' => '7',
                ],
            ],
            'note_globale' => '14,5',
            'mention' => 'Très bien',
            'connaissances' => ['Rédaction administrative'],
            'avis_notateur' => ['texte' => 'Agent rigoureux.', 'date' => '01/10/2026'],
            'avis' => [
                [
                    'titre' => 'Le Chef de service',
                    'texte' => 'Avis favorable.',
                    'signataire' => 'OKEMBA Paul',
                    'date' => '02/10/2026',
                ],
            ],
            'reclamation' => null,
            'signature_evaluateur' => '01/10/2026',
            'signature_evalue' => '03/10/2026',
            'commission' => [
                'lignes' => [
                    ['libelle' => 'Note harmonisée de la commission préparatoire', 'note' => '14', 'bareme' => '/ 20'],
                ],
                'synthese' => 'Note confirmée.',
                'decision' => 'Favorable (avancement accordé) — 1 échelon(s)',
            ],
        ];
    }
}
