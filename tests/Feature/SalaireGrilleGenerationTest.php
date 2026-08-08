<?php

namespace Tests\Feature;

use App\Models\Salaire;
use App\Models\User;
use App\Services\SalaireService;
use Database\Seeders\CategorieSeeder;
use Database\Seeders\ClassegrillesalarialeSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\ParametregrileSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalaireGrilleGenerationTest extends TestCase
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

    public function test_classe_i_echelon_1_vaut_147000_avec_point_300(): void
    {
        $this->seed([
            GradeSeeder::class,
            CategorieSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
        ]);

        $result = app(SalaireService::class)->generateGrille(300.0);

        $this->assertSame(120, $result['total']);
        $this->assertSame(300.0, $result['valeur_point_indice']);

        $ligne = Salaire::query()
            ->whereHas('classe', fn ($q) => $q->where('coefficient', 45))
            ->where('echelon', 1)
            ->first();

        $this->assertNotNull($ligne);
        $this->assertSame(490, $ligne->indice);
        $this->assertEquals(147000.0, (float) $ligne->salaire);
    }

    public function test_endpoint_generation_enrichit_total_et_point(): void
    {
        $this->seed([
            GradeSeeder::class,
            CategorieSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
        ]);
        $this->actingAsRh();

        $response = $this->postJson('/api/salaires/generation', [
            'valeur_point_indice' => 300,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 120)
            ->assertJsonPath('valeur_point_indice', 300)
            ->assertJsonPath('echelon_depart', 1)
            ->assertJsonPath('echelon_fin', 12);
    }

    public function test_generation_respecte_echelon_depart_et_fin(): void
    {
        $this->seed([
            GradeSeeder::class,
            CategorieSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
        ]);

        \App\Models\Parametregrille::query()->first()->update([
            'echelon_depart' => 1,
            'echelon_fin'    => 2,
        ]);

        $result = app(SalaireService::class)->generateGrille(300.0);

        $this->assertSame(20, $result['total']); // 10 classes × 2 échelons
        $this->assertSame(1, $result['echelon_depart']);
        $this->assertSame(2, $result['echelon_fin']);
        $this->assertGreaterThan(0, Salaire::query()->where('echelon', 2)->count());
        $this->assertSame(0, Salaire::query()->where('echelon', 3)->count());
    }

    public function test_generation_sans_auth_est_refusee(): void
    {
        $response = $this->postJson('/api/salaires/generation', [
            'valeur_point_indice' => 300,
        ]);

        $response->assertUnauthorized();
    }
}
