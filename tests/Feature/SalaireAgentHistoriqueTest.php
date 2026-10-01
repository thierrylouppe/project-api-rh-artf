<?php

namespace Tests\Feature;

use App\Enums\TypeChangementSalaireAgent;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Echelon;
use App\Models\Grade;
use App\Models\User;
use App\Services\SalaireAgentService;
use App\Services\SalaireService;
use Database\Seeders\CategorieSeeder;
use Database\Seeders\ClassegrillesalarialeSeeder;
use Database\Seeders\EchelonSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\ParametregrileSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalaireAgentHistoriqueTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRh(): User
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_historique_chronologique_avec_variations(): void
    {
        $this->seed([
            GradeSeeder::class,
            CategorieSeeder::class,
            EchelonSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
        ]);

        app(SalaireService::class)->generateGrille(300.0);

        $categorie = Categorie::where('nom', 'Classe I')->firstOrFail();
        $grade = Grade::where('nom', 'Personnel de service')->firstOrFail();
        $echelon1 = Echelon::where('numero', 1)->firstOrFail();

        $agent = Agent::query()->create([
            'nom'            => 'Test',
            'prenom'         => 'Salaire',
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'categorie_id'   => $categorie->id,
            'grade_id'       => $grade->id,
            'echelon_id'     => $echelon1->id,
            'statut'         => 'actif',
        ]);

        $service = app(SalaireAgentService::class);
        $initial = $service->creerSalaireInitial($agent);
        $this->assertNotNull($initial);
        $this->assertSame(TypeChangementSalaireAgent::INITIAL, $initial->type_changement);
        $this->assertEquals(147000.0, (float) $initial->montant_base);

        $avance = $service->avancerEchelon($agent->id, 'Avancement après évaluation');
        $this->assertSame(TypeChangementSalaireAgent::AVANCEMENT_ECHELON, $avance->type_changement);
        $this->assertSame(2, $avance->echelon);
        $this->assertEquals(160500.0, (float) $avance->montant_base);

        $this->actingAsRh();

        $response = $this->getJson("/api/integration/agents/{$agent->id}/salaires/historique");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.echelon', 1)
            ->assertJsonPath('data.0.type_changement', 'initial')
            ->assertJsonPath('data.0.variation_echelon', null)
            ->assertJsonPath('data.1.echelon', 2)
            ->assertJsonPath('data.1.type_changement', 'avancement_echelon')
            ->assertJsonPath('data.1.echelon_precedent', 1)
            ->assertJsonPath('data.1.variation_echelon', 1)
            ->assertJsonPath('data.1.motif', 'Avancement après évaluation');

        $this->assertEquals(147000.0, (float) $response->json('data.1.montant_precedent'));
        $this->assertEquals(13500.0, (float) $response->json('data.1.variation_montant'));
    }

    public function test_bulletin_pdf_du_salaire_actif(): void
    {
        $this->seed([
            GradeSeeder::class,
            CategorieSeeder::class,
            EchelonSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
        ]);

        app(SalaireService::class)->generateGrille(300.0);

        $categorie = Categorie::where('nom', 'Classe I')->firstOrFail();
        $grade = Grade::where('nom', 'Personnel de service')->firstOrFail();
        $echelon1 = Echelon::where('numero', 1)->firstOrFail();

        $agent = Agent::query()->create([
            'nom'            => 'Bulletin',
            'prenom'         => 'Test',
            'date_naissance' => '1990-01-01',
            'genre'          => 'F',
            'categorie_id'   => $categorie->id,
            'grade_id'       => $grade->id,
            'echelon_id'     => $echelon1->id,
            'statut'         => 'actif',
            'matricule'     => 'ARTF-TEST-001',
        ]);

        app(SalaireAgentService::class)->creerSalaireInitial($agent);

        $this->actingAsRh();

        $response = $this->get("/api/integration/agents/{$agent->id}/salaires/bulletin");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }
}
