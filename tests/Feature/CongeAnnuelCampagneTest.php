<?php

namespace Tests\Feature;

use App\Enums\StatutAffectation;
use App\Enums\StatutDemandeConge;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\CongeSolde;
use App\Models\Direction;
use App\Models\Localite;
use App\Models\RegleAcquisitionConge;
use App\Models\TypeConge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CongeAnnuelCampagneTest extends TestCase
{
    use RefreshDatabase;

    private User $demandeur;

    private User $chefUser;

    private User $rhUser;

    private Agent $agent;

    private Agent $chef;

    private TypeConge $annuel;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-02');

        foreach (['consulter-conges', 'creer-conges', 'valider-conges'] as $name) {
            Permission::findOrCreate($name, 'api');
        }

        Role::findOrCreate('rh', 'api');
        Role::findOrCreate('chef-service', 'api');
        Role::findOrCreate('admin', 'api');

        $this->agent = $this->creerAgent('Jean', 'Agent', '2024-01-01');
        $this->chef  = $this->creerAgent('Marie', 'Chef', '2018-01-01');

        $this->demandeur = User::factory()->create(['agent_id' => $this->agent->id]);
        $this->demandeur->givePermissionTo(['consulter-conges', 'creer-conges']);

        $this->chefUser = User::factory()->create(['agent_id' => $this->chef->id]);
        $this->chefUser->givePermissionTo(['consulter-conges', 'valider-conges']);
        $this->chefUser->assignRole('chef-service');

        $this->rhUser = User::factory()->create();
        $this->rhUser->givePermissionTo(['consulter-conges', 'valider-conges', 'creer-conges']);
        $this->rhUser->assignRole('rh');

        $localite = Localite::create(['nom' => 'Brazzaville']);
        $admin    = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $direction = Direction::create(['nom' => 'DRHL', 'administration_id' => $admin->id]);

        Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Direction::class,
            'structurable_id'           => $direction->id,
            'superieur_hierarchique_id' => $this->chef->id,
            'date_affectation'          => '2026-01-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->demandeur->id,
        ]);

        $this->annuel = TypeConge::create([
            'nom'          => 'Congé annuel',
            'jours_max'    => 30,
            'necessite_n1' => true,
            'necessite_rh' => true,
            'necessite_dg' => false,
            'debite_solde' => true,
        ]);

        RegleAcquisitionConge::create([
            'type_conge_id'  => $this->annuel->id,
            'jours_par_mois' => 2.5,
            'jours_max'      => 30,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_campagne_puis_attribution_debite_le_solde_apres_cloture(): void
    {
        Sanctum::actingAs($this->demandeur);
        $this->postJson('/api/conges-annuels/campagnes', $this->payloadCampagne())
            ->assertForbidden();

        Sanctum::actingAs($this->rhUser);
        $campagneId = $this->postJson('/api/conges-annuels/campagnes', $this->payloadCampagne())
            ->assertCreated()
            ->assertJsonPath('data.statut', 'brouillon')
            ->json('data.id');

        $this->postJson("/api/conges-annuels/campagnes/{$campagneId}/ouvrir")
            ->assertOk()
            ->assertJsonPath('data.statut', 'ouverte');

        Sanctum::actingAs($this->demandeur);
        $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-09-05',
        ])->assertStatus(422);

        $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-09-07',
        ])->assertCreated()
            ->assertJsonPath('data.origine', 'campagne')
            ->assertJsonPath('data.date_debut', '2026-09-07')
            ->assertJsonPath('data.date_fin', '2026-10-16')
            ->assertJsonPath('data.date_reprise', '2026-10-19')
            ->assertJsonPath('data.nb_jours', 30)
            ->assertJsonPath('data.statut', StatutDemandeConge::SOUMISE->value);

        $id = $this->getJson('/api/conges-annuels/demandes')->assertOk()->json('data.0.id');

        Sanctum::actingAs($this->rhUser);
        $sans = collect($this->getJson("/api/conges-annuels/campagnes/{$campagneId}/sans-proposition")->assertOk()->json('data'))
            ->pluck('id');
        $this->assertFalse($sans->contains($this->agent->id));
        $this->assertTrue($sans->contains($this->chef->id));

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges-annuels/demandes/{$id}/valider-n1")
            ->assertStatus(422);

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges-annuels/campagnes/{$campagneId}/cloturer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'cloturee');

        Sanctum::actingAs($this->demandeur);
        $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-10-05',
        ])->assertStatus(422);
        $this->postJson("/api/conges-annuels/demandes/{$id}/annuler")->assertStatus(422);

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges-annuels/demandes/{$id}/valider-n1")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_N1->value);

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges-annuels/demandes/{$id}/valider-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_RH->value);

        $this->getJson("/api/conges-annuels/agents/{$this->agent->id}/solde?annee=2026")
            ->assertOk()
            ->assertJsonPath('data.solde_actuel', 0)
            ->assertJsonPath('data.jours_reportes', 0);

        $this->get("/api/conges-annuels/demandes/{$id}/attestation")->assertOk();
    }

    public function test_annulation_possible_tant_que_la_campagne_est_ouverte(): void
    {
        $campagneId = $this->ouvrirCampagne();

        Sanctum::actingAs($this->demandeur);
        $id = $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-09-07',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/conges-annuels/demandes/{$id}/annuler")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::ANNULEE->value);

        Sanctum::actingAs($this->rhUser);
        $sans = collect($this->getJson("/api/conges-annuels/campagnes/{$campagneId}/sans-proposition")->json('data'))
            ->pluck('id');
        $this->assertTrue($sans->contains($this->agent->id));
    }

    public function test_droit_acquis_apres_cloture_reserve_a_moins_de_12_mois(): void
    {
        $this->ouvrirCampagne();
        Sanctum::actingAs($this->rhUser);
        $campagneId = $this->getJson('/api/conges-annuels/campagnes')->json('data.0.id');
        $this->postJson("/api/conges-annuels/campagnes/{$campagneId}/cloturer")->assertOk();

        Sanctum::actingAs($this->demandeur);
        $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-11-02',
            'origine'    => 'apres_cloture',
        ])->assertStatus(422);

        $this->agent->update(['date_prise_service' => '2025-11-01']);

        $id = $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-11-02',
            'origine'    => 'apres_cloture',
        ])->assertCreated()
            ->assertJsonPath('data.origine', 'apres_cloture')
            ->assertJsonPath('data.nb_jours', 30)
            ->json('data.id');

        $this->postJson('/api/conges-annuels/demandes', [
            'agent_id'   => $this->agent->id,
            'date_debut' => '2026-10-05',
            'origine'    => 'apres_cloture',
        ])->assertStatus(422);

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges-annuels/demandes/{$id}/valider-n1")->assertOk();

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges-annuels/demandes/{$id}/valider-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_RH->value);
    }

    public function test_report_necessite_de_service_plafonne_a_60_jours(): void
    {
        CongeSolde::create([
            'agent_id'         => $this->agent->id,
            'type_conge_id'    => $this->annuel->id,
            'annee'            => 2025,
            'solde_initial'    => 30,
            'solde_actuel'     => 10,
            'jours_anciennete' => 0,
            'jours_reportes'   => 0,
        ]);

        Sanctum::actingAs($this->demandeur);
        $this->postJson('/api/conges-annuels/reports', [
            'agent_id'     => $this->agent->id,
            'annee_source' => 2025,
            'motif'        => 'Nécessité de service',
        ])->assertForbidden();

        Sanctum::actingAs($this->chefUser);
        $reportId = $this->postJson('/api/conges-annuels/reports', [
            'agent_id'     => $this->agent->id,
            'annee_source' => 2025,
            'motif'        => 'Nécessité de service',
        ])->assertCreated()
            ->assertJsonPath('data.jours', 10)
            ->assertJsonPath('data.statut', 'propose')
            ->json('data.id');

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges-annuels/reports/{$reportId}/accorder")->assertForbidden();

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges-annuels/reports/{$reportId}/accorder")
            ->assertOk()
            ->assertJsonPath('data.statut', 'accorde');

        $this->getJson("/api/conges-annuels/agents/{$this->agent->id}/solde?annee=2026")
            ->assertOk()
            ->assertJsonPath('data.solde_actuel', 40)
            ->assertJsonPath('data.jours_reportes', 10);

        $this->assertSame(0.0, (float) CongeSolde::query()
            ->where('agent_id', $this->agent->id)
            ->where('annee', 2025)
            ->value('solde_actuel'));
    }

    public function test_report_refuse_au_dela_de_60_jours(): void
    {
        CongeSolde::create([
            'agent_id'         => $this->agent->id,
            'type_conge_id'    => $this->annuel->id,
            'annee'            => 2025,
            'solde_initial'    => 61,
            'solde_actuel'     => 61,
            'jours_anciennete' => 0,
            'jours_reportes'   => 0,
        ]);

        Sanctum::actingAs($this->chefUser);
        $this->postJson('/api/conges-annuels/reports', [
            'agent_id'     => $this->agent->id,
            'annee_source' => 2025,
            'motif'        => 'Reliquat trop élevé',
        ])->assertStatus(422);
    }

    private function ouvrirCampagne(): int
    {
        Sanctum::actingAs($this->rhUser);

        $id = $this->postJson('/api/conges-annuels/campagnes', $this->payloadCampagne())
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/conges-annuels/campagnes/{$id}/ouvrir")->assertOk();

        return (int) $id;
    }

    private function payloadCampagne(): array
    {
        return [
            'annee'          => 2026,
            'date_ouverture' => '2026-01-05',
            'date_cloture'   => '2026-03-31',
        ];
    }

    private function creerAgent(string $prenom, string $nom, string $priseService): Agent
    {
        return Agent::create([
            'nom'                => $nom,
            'prenom'             => $prenom,
            'date_naissance'     => '1990-01-01',
            'genre'              => 'M',
            'statut'             => 'actif',
            'date_prise_service' => $priseService,
        ]);
    }
}
