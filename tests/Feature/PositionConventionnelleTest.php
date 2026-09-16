<?php

namespace Tests\Feature;

use App\Enums\StatutAgent;
use App\Enums\StatutNomination;
use App\Jobs\PositionConventionnelleEcheanceJob;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Echelon;
use App\Models\Nomination;
use App\Models\PositionConventionnelle;
use App\Models\SalaireAgent;
use App\Models\User;
use App\Services\PositionConventionnelleService;
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

class PositionConventionnelleTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private User $dg;

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
        ]);

        app(SalaireService::class)->generateGrille(300.0);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));

        $this->dg = User::factory()->create();
        $this->dg->assignRole(Role::findByName('directeur-general', 'api'));
    }

    public function test_detachement_moins_de_cinq_ans_retourne_422_anciennete(): void
    {
        $agent = $this->creerAgent(now()->subYears(2)->toDateString());

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/positions', $this->payloadDetachement($agent->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['anciennete']);
    }

    public function test_detachement_actif_cloture_le_salaire(): void
    {
        $agent = $this->creerAgent(now()->subYears(6)->toDateString());
        $this->assertNotNull(SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first());

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/positions', $this->payloadDetachement($agent->id))
            ->assertCreated()
            ->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/positions/{$id}/approuver")
            ->assertOk()
            ->assertJsonPath('data.statut', 'active')
            ->assertJsonPath('data.coupe_remuneration', true)
            ->assertJsonPath('data.peut_renouveler', true)
            ->assertJsonPath('data.prochaine_etape', 'cloturer');

        $this->assertSame(StatutAgent::DETACHEMENT->value, $agent->fresh()->statut);
        $this->assertNull(SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first());
    }

    public function test_put_statut_detachement_retourne_422(): void
    {
        $agent = $this->creerAgent(now()->subYears(6)->toDateString());

        Sanctum::actingAs($this->rh);
        $this->putJson("/api/integration/agents/{$agent->id}", [
            'statut' => 'detachement',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.statut.0', 'Utiliser POST /carriere/positions');
    }

    public function test_disponibilite_troisieme_renouvellement_refuse(): void
    {
        $agent = $this->creerAgent(now()->subYears(4)->toDateString());

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/positions', [
            'agent_id'   => $agent->id,
            'type'       => 'disponibilite',
            'date_debut' => now()->addMonths(3)->toDateString(),
            'date_fin'   => now()->addMonths(3)->addYears(2)->toDateString(),
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/positions/{$id}/approuver")->assertOk();

        $this->postJson("/api/carriere/positions/{$id}/renouveler", [
            'date_debut' => now()->addYears(2)->addMonths(3)->toDateString(),
            'date_fin'   => now()->addYears(4)->addMonths(3)->toDateString(),
        ])->assertOk()->assertJsonPath('data.nb_renouvellements', 1);

        $this->postJson("/api/carriere/positions/{$id}/renouveler", [
            'date_debut' => now()->addYears(4)->addMonths(3)->toDateString(),
            'date_fin'   => now()->addYears(6)->addMonths(3)->toDateString(),
        ])->assertOk()->assertJsonPath('data.nb_renouvellements', 2);

        $this->postJson("/api/carriere/positions/{$id}/renouveler", [
            'date_debut' => now()->addYears(6)->addMonths(3)->toDateString(),
            'date_fin'   => now()->addYears(8)->addMonths(3)->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['renouveler']);
    }

    public function test_disponibilite_cloture_nomination_et_bloque_avancer_echelon(): void
    {
        $agent = $this->creerAgent(now()->subYears(4)->toDateString());
        Nomination::create([
            'agent_id'          => $agent->id,
            'poste'             => 'Chef de bureau',
            'date_debut'        => now()->subYear()->toDateString(),
            'statut'            => StatutNomination::ACTIVE,
            'type_acte'         => 'decision',
            'structurable_type' => 'App\\Models\\Bureau',
            'structurable_id'   => 1,
            'created_by'        => $this->rh->id,
        ]);

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/positions', [
            'agent_id'   => $agent->id,
            'type'       => 'disponibilite',
            'date_debut' => now()->addMonths(3)->toDateString(),
            'date_fin'   => now()->addMonths(3)->addYears(1)->toDateString(),
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/positions/{$id}/approuver")->assertOk();

        $this->assertSame(StatutNomination::CLOTUREE, Nomination::where('agent_id', $agent->id)->first()->statut);

        Sanctum::actingAs($this->rh);
        $this->postJson("/api/carriere/agents/{$agent->id}/salaires/avancer-echelon")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['statut']);
    }

    public function test_position_exceptionnelle_conserve_le_salaire(): void
    {
        $agent = $this->creerAgent(now()->subYears(2)->toDateString());

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/positions', [
            'agent_id'   => $agent->id,
            'type'       => 'position_exceptionnelle',
            'date_debut' => now()->toDateString(),
            'date_fin'   => now()->addYear()->toDateString(),
            'commentaire'=> 'Cabinet ministériel.',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/positions/{$id}/approuver")
            ->assertOk()
            ->assertJsonPath('data.coupe_remuneration', false);

        $this->assertNotNull(SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first());
        $this->assertSame(StatutAgent::POSITION_EXCEPTIONNELLE->value, $agent->fresh()->statut);
    }

    public function test_rh_ne_peut_pas_approuver(): void
    {
        $agent = $this->creerAgent(now()->subYears(6)->toDateString());

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/positions', $this->payloadDetachement($agent->id))
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/carriere/positions/{$id}/approuver")->assertForbidden();
    }

    public function test_job_echeance_notifie_la_rh(): void
    {
        $agent = $this->creerAgent(now()->subYears(6)->toDateString());

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/positions', $this->payloadDetachement($agent->id))->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/positions/{$id}/approuver")->assertOk();

        PositionConventionnelle::findOrFail($id)
            ->update(['date_fin' => now()->addDays(7)->toDateString()]);

        (new PositionConventionnelleEcheanceJob())->handle(app(PositionConventionnelleService::class));

        $actions = $this->rh->fresh()->notifications()->get()->pluck('data.action');
        $this->assertTrue($actions->contains('echeance'));
    }

    private function creerAgent(string $prise): Agent
    {
        $categorie  = Categorie::where('nom', 'Classe I')->firstOrFail();
        $classe     = Classegrillesalariale::where('categorie_id', $categorie->id)
            ->with(['grade', 'categorie'])
            ->firstOrFail();
        $echelonMod = Echelon::where('numero', 1)->firstOrFail();

        $agent = Agent::query()->create([
            'nom'                => 'Position',
            'prenom'             => 'Ccn',
            'date_naissance'     => '1985-01-01',
            'genre'              => 'M',
            'categorie_id'       => $classe->categorie_id,
            'grade_id'           => $classe->grade_id,
            'echelon_id'         => $echelonMod->id,
            'date_prise_service' => $prise,
            'statut'             => 'actif',
        ]);

        app(SalaireAgentService::class)->creerSalaireInitial($agent);

        return $agent->fresh();
    }

    private function payloadDetachement(int $agentId): array
    {
        return [
            'agent_id'           => $agentId,
            'type'               => 'detachement',
            'date_debut'         => now()->addMonths(3)->toDateString(),
            'date_fin'           => now()->addMonths(3)->addYears(2)->toDateString(),
            'organisme_accueil'  => 'Ministère des Finances',
            'consentement_agent' => true,
        ];
    }
}
