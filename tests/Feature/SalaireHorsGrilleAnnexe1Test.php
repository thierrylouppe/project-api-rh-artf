<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Diplome;
use App\Models\Echelon;
use App\Models\Fonction;
use App\Models\InformationsProfessionnelle;
use App\Models\User;
use App\Services\SalaireAgentService;
use App\Services\SalaireService;
use Database\Seeders\CategorieSeeder;
use Database\Seeders\ClassegrillesalarialeSeeder;
use Database\Seeders\DiplomeSeeder;
use Database\Seeders\EchelonSeeder;
use Database\Seeders\FonctionSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\ParametregrileSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalaireHorsGrilleAnnexe1Test extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            GradeSeeder::class,
            CategorieSeeder::class,
            EchelonSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
            FonctionSeeder::class,
            DiplomeSeeder::class,
        ]);

        app(SalaireService::class)->generateGrille(300.0);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($this->rh);
    }

    public function test_creation_salaire_dg_retourne_salaire_fonctionnel(): void
    {
        $dg    = Fonction::where('sigle', 'DG')->firstOrFail();
        $agent = $this->creerAgent('Classe I', 1, $dg);

        $this->postJson('/api/salaires-agents', ['agent_id' => $agent->id])
            ->assertCreated()
            ->assertJsonPath('data', null)
            ->assertJsonPath('meta.salaire_fonctionnel', true)
            ->assertJsonPath('meta.hors_grille', true);

        $this->assertNull(app(SalaireAgentService::class)->getActuel($agent->id));

        $this->getJson("/api/carriere/agents/{$agent->id}/salaires/actuel")
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('meta.hors_grille', true);

        $this->postJson("/api/carriere/agents/{$agent->id}/salaires/avancer-echelon")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fonction']);
    }

    public function test_licence_bonifie_un_echelon_a_lentree(): void
    {
        $agent   = $this->creerAgent('Classe VII', 1);
        $diplome = Diplome::where('sigle', 'LIC')->firstOrFail();
        $this->assertSame(1, $diplome->bonification_echelons);

        InformationsProfessionnelle::create([
            'agent_id'   => $agent->id,
            'diplome_id' => $diplome->id,
        ]);

        $this->postJson('/api/salaires-agents', ['agent_id' => $agent->id])
            ->assertCreated()
            ->assertJsonPath('data.echelon', 2)
            ->assertJsonPath('meta.annexe1.bonification_echelons', 1)
            ->assertJsonPath('meta.annexe1.echelon_effectif', 2);

        $this->assertSame(2, $agent->fresh()->echelon->numero);
    }

    public function test_classes_i_ii_ne_sont_plus_des_diplomes_et_maitrise_existe(): void
    {
        $this->assertNull(Diplome::where('sigle', 'AS')->first());
        $this->assertNull(Diplome::where('sigle', 'GB')->first());
        $this->assertNotNull(Diplome::where('sigle', 'MAIT')->first());
        $this->assertSame(2, Diplome::where('sigle', 'DOC')->value('bonification_echelons'));
        $this->assertSame(0, Diplome::where('sigle', 'MAIT')->value('bonification_echelons'));
    }

    public function test_agent_dg_expose_hors_grille(): void
    {
        $dg    = Fonction::where('sigle', 'DG')->firstOrFail();
        $agent = $this->creerAgent('Classe I', 1, $dg);

        $this->getJson("/api/integration/agents/{$agent->id}")
            ->assertOk()
            ->assertJsonPath('data.hors_grille', true);
    }

    private function creerAgent(string $categorieNom, int $echelon, ?Fonction $fonction = null): Agent
    {
        $categorie  = Categorie::where('nom', $categorieNom)->firstOrFail();
        $classe     = Classegrillesalariale::where('categorie_id', $categorie->id)->firstOrFail();
        $echelonMod = Echelon::where('numero', $echelon)->firstOrFail();

        return Agent::query()->create([
            'nom'                => 'LotD',
            'prenom'             => 'Ccn',
            'date_naissance'     => '1980-01-01',
            'genre'              => 'M',
            'categorie_id'       => $classe->categorie_id,
            'grade_id'           => $classe->grade_id,
            'echelon_id'         => $echelonMod->id,
            'fonction_id'        => $fonction?->id,
            'date_prise_service' => '2018-01-01',
            'statut'             => 'actif',
        ]);
    }
}
