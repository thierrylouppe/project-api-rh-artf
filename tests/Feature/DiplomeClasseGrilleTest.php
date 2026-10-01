<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Diplome;
use App\Models\Echelon;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiplomeClasseGrilleTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_inclut_classe_grille_avec_echelon_de_depart(): void
    {
        $categorie = Categorie::create(['nom' => 'A1', 'sigle' => 'A1']);
        $grade     = Grade::create(['nom' => 'Attaché', 'sigle' => 'ATT', 'niveau' => 1]);
        $echelon   = Echelon::create(['nom' => 'Échelon 1', 'numero' => 1]);

        $classe = Classegrillesalariale::create([
            'categorie_id' => $categorie->id,
            'grade_id'     => $grade->id,
            'coefficient'  => 140,
        ]);

        $avecClasse = Diplome::create([
            'nom'                      => 'Master en Informatique',
            'sigle'                    => 'M2',
            'classegrillesalariale_id' => $classe->id,
        ]);

        $sansClasse = Diplome::create([
            'nom'   => 'Diplôme sans grille',
            'sigle' => 'DSG',
        ]);

        $response = $this->getJson('/api/diplomes');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $avec = collect($response->json('data'))->firstWhere('id', $avecClasse->id);
        $sans = collect($response->json('data'))->firstWhere('id', $sansClasse->id);

        $this->assertNotNull($avec);
        $this->assertSame($classe->id, $avec['classegrillesalariale_id']);
        $this->assertSame([
            'id'           => $classe->id,
            'coefficient'  => $classe->coefficient,
            'categorie'    => 'A1',
            'categorie_id' => $categorie->id,
            'grade'        => 'Attaché',
            'grade_id'     => $grade->id,
            'echelon'      => 'Échelon 1',
            'echelon_id'   => $echelon->id,
        ], $avec['classe_grille']);
        $this->assertArrayNotHasKey('fonction_id', $avec['classe_grille']);
        $this->assertArrayNotHasKey('fonction', $avec['classe_grille']);

        $this->assertNotNull($sans);
        $this->assertNull($sans['classegrillesalariale_id']);
        $this->assertNull($sans['classe_grille']);
    }
}
