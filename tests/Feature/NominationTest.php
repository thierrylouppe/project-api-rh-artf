<?php

namespace Tests\Feature;

use App\Enums\StatutAffectation;
use App\Enums\StatutDossier;
use App\Enums\StatutNomination;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\DossierIntegration;
use App\Models\Localite;
use App\Models\Nomination;
use App\Models\Service;
use App\Models\TypeIntegration;
use App\Models\User;
use App\Models\ValidationWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NominationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Agent $agent;

    private Direction $direction;

    private Service $service;

    private Bureau $bureau;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $localite = Localite::create(['nom' => 'Brazzaville']);
        $admin    = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $this->direction = Direction::create(['nom' => 'Direction Test', 'administration_id' => $admin->id]);
        $this->service   = Service::create(['nom' => 'Service Test', 'direction_id' => $this->direction->id]);
        $this->bureau    = Bureau::create(['nom' => 'Bureau Test', 'service_id' => $this->service->id]);

        $this->agent = $this->creerAgent('Jean', 'Agent');
    }

    public function test_creer_via_carriere_et_alias_integration(): void
    {
        $this->postJson('/api/carriere/nominations', $this->payloadNomination($this->agent))
            ->assertCreated()
            ->assertJsonPath('data.statut', StatutNomination::EN_ATTENTE->value)
            ->assertJsonPath('data.statut_label', StatutNomination::EN_ATTENTE->label())
            ->assertJsonPath('data.poste', 'Chef de Service');

        $this->postJson('/api/integration/nominations', $this->payloadNomination(
            $this->creerAgent('Alias', 'Fe'),
            'Chef de Bureau',
            Bureau::class,
            $this->bureau->id
        ))->assertCreated();
    }

    public function test_structure_inexistante_retourne_422(): void
    {
        $this->postJson('/api/carriere/nominations', [
            ...$this->payloadNomination($this->agent),
            'structurable_id' => 99999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['structurable_id']);
    }

    public function test_poste_incoherent_avec_la_structure_retourne_422(): void
    {
        $this->postJson('/api/carriere/nominations', [
            ...$this->payloadNomination($this->agent),
            'poste'             => 'Chef de Bureau',
            'structurable_type' => Direction::class,
            'structurable_id'   => $this->direction->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['poste']);
    }

    public function test_circuit_dernier_niveau_passe_en_approuvee(): void
    {
        $nomination = $this->creerNomination($this->agent);

        $validations = ValidationWorkflow::query()
            ->where('validable_type', Nomination::class)
            ->where('validable_id', $nomination->id)
            ->orderBy('ordre')
            ->get();

        $this->assertCount(5, $validations);

        foreach ($validations as $validation) {
            $this->postJson("/api/integration/validations/{$validation->id}/approuver", [
                'commentaire' => 'Approuvé niveau '.$validation->ordre,
            ])->assertOk();
        }

        $this->assertSame(
            StatutNomination::APPROUVEE,
            $nomination->fresh()->statut
        );
    }

    public function test_activer_cloture_ancienne_structure_et_agent_sans_changer_le_dossier(): void
    {
        $autreAgent = $this->creerAgent('Marie', 'Chef');

        $activeStructure = $this->creerNomination(
            $autreAgent,
            StatutNomination::ACTIVE,
            'Chef de Service',
            Service::class,
            $this->service->id
        );
        $activeAgent = $this->creerNomination(
            $this->agent,
            StatutNomination::ACTIVE,
            'Chef de Bureau',
            Bureau::class,
            $this->bureau->id
        );
        $nouvelle = $this->creerNomination(
            $this->agent,
            StatutNomination::APPROUVEE,
            'Chef de Service',
            Service::class,
            $this->service->id
        );

        $dossier = $this->creerDossierIntegre();

        $this->postJson("/api/carriere/nominations/{$nouvelle->id}/activer", [
            'dossier_integration_id' => $dossier->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutNomination::ACTIVE->value);

        $this->assertSame(StatutNomination::CLOTUREE, $activeStructure->fresh()->statut);
        $this->assertSame(StatutNomination::CLOTUREE, $activeAgent->fresh()->statut);
        $this->assertSame(StatutDossier::INTEGRE, $dossier->fresh()->statut);
    }

    public function test_activer_depuis_en_attente_retourne_422(): void
    {
        $nomination = $this->creerNomination($this->agent);

        $this->postJson("/api/carriere/nominations/{$nomination->id}/activer")
            ->assertStatus(422);
    }

    public function test_rejeter_et_cloturer(): void
    {
        $aRejeter = $this->creerNomination($this->agent);
        $this->postJson("/api/carriere/nominations/{$aRejeter->id}/rejeter", [
            'commentaire' => 'Profil incompatible avec le poste',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutNomination::REJETEE->value);

        $active = $this->creerNomination($this->agent, StatutNomination::ACTIVE);
        $this->postJson("/api/carriere/nominations/{$active->id}/cloturer", [
            'date_fin' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutNomination::CLOTUREE->value);

        $this->postJson("/api/carriere/nominations/{$active->id}/cloturer")
            ->assertStatus(422);
    }

    public function test_liste_par_agent(): void
    {
        $this->creerNomination($this->agent);

        $this->getJson("/api/carriere/agents/{$this->agent->id}/nominations")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_postes_vacants_exclut_la_structure_occupee(): void
    {
        $this->creerNomination($this->agent, StatutNomination::ACTIVE);

        $response = $this->getJson('/api/carriere/nominations/postes-vacants')
            ->assertOk();

        $idsService = collect($response->json('data'))
            ->where('structurable_type', Service::class)
            ->pluck('structurable_id');

        $this->assertFalse($idsService->contains($this->service->id));
        $this->assertTrue(
            collect($response->json('data'))
                ->contains(fn (array $poste) => $poste['structurable_type'] === Bureau::class
                    && $poste['structurable_id'] === $this->bureau->id)
        );
    }

    public function test_agents_sous_autorite(): void
    {
        $chef = $this->creerAgent('Paul', 'Chef');
        $this->creerNomination($chef, StatutNomination::ACTIVE);

        Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Service::class,
            'structurable_id'           => $this->service->id,
            'superieur_hierarchique_id' => $chef->id,
            'date_affectation'          => '2026-07-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->user->id,
        ]);

        $this->getJson("/api/carriere/nominations/chefs/{$chef->id}/agents-sous-autorite")
            ->assertOk()
            ->assertJsonPath('data.chef.id', $chef->id)
            ->assertJsonPath('data.agents.0.agent.id', $this->agent->id);
    }

    public function test_historique_exclut_la_nomination_active(): void
    {
        $this->creerNomination($this->agent, StatutNomination::ACTIVE);
        $this->creerNomination(
            $this->agent,
            StatutNomination::CLOTUREE,
            'Chef de Bureau',
            Bureau::class,
            $this->bureau->id
        );

        $this->getJson("/api/carriere/agents/{$this->agent->id}/nominations/historique")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.statut', StatutNomination::CLOTUREE->value);
    }

    public function test_update_uniquement_si_en_attente(): void
    {
        $enAttente = $this->creerNomination($this->agent);
        $this->putJson("/api/carriere/nominations/{$enAttente->id}", [
            ...$this->payloadNomination($this->agent),
            'date_debut' => '2026-10-01',
            'type_acte'  => 'arrete',
        ])
            ->assertOk()
            ->assertJsonPath('data.date_debut', '2026-10-01')
            ->assertJsonPath('data.type_acte', 'arrete');

        $active = $this->creerNomination(
            $this->creerAgent('Luc', 'Actif'),
            StatutNomination::ACTIVE
        );
        $this->putJson("/api/carriere/nominations/{$active->id}", $this->payloadNomination($active->agent))
            ->assertStatus(422);
    }

    public function test_telecharger_acte_pdf(): void
    {
        $nomination = $this->creerNomination($this->agent);

        $this->get("/api/carriere/nominations/{$nomination->id}/acte")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_synthese_carriere_agent(): void
    {
        $this->creerNomination($this->agent, StatutNomination::ACTIVE);

        $this->getJson("/api/carriere/agents/{$this->agent->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->agent->id)
            ->assertJsonPath('data.nomination_active.poste', 'Chef de Service')
            ->assertJsonPath('data.contrat_actif', null)
            ->assertJsonPath('data.affectation_active', null)
            ->assertJsonPath('data.salaire_actuel', null);
    }

    public function test_notification_database_a_la_creation(): void
    {
        $this->creerNomination($this->agent);

        $this->assertSame(1, $this->user->notifications()->count());
        $this->assertSame('creee', $this->user->notifications()->first()->data['action']);
    }

    private function payloadNomination(
        Agent $agent,
        string $poste = 'Chef de Service',
        string $type = Service::class,
        ?int $structureId = null,
    ): array {
        return [
            'agent_id'          => $agent->id,
            'poste'             => $poste,
            'structurable_type' => $type,
            'structurable_id'   => $structureId ?? $this->service->id,
            'date_debut'        => '2026-09-01',
            'type_acte'         => 'decision',
        ];
    }

    private function creerNomination(
        Agent $agent,
        ?StatutNomination $statut = null,
        string $poste = 'Chef de Service',
        string $type = Service::class,
        ?int $structureId = null,
    ): Nomination {
        $response = $this->postJson('/api/carriere/nominations', $this->payloadNomination(
            $agent,
            $poste,
            $type,
            $structureId
        ));
        $response->assertCreated();

        $nomination = Nomination::findOrFail($response->json('data.id'));

        if ($statut !== null) {
            $nomination->update(['statut' => $statut]);
        }

        return $nomination->fresh();
    }

    private function creerAgent(string $prenom, string $nom): Agent
    {
        return Agent::create([
            'nom'            => $nom,
            'prenom'         => $prenom,
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'statut'         => 'actif',
        ]);
    }

    private function creerDossierIntegre(): DossierIntegration
    {
        $type = TypeIntegration::create(['nom' => 'Recrutement externe test']);

        return DossierIntegration::create([
            'reference'           => 'DOS-TEST-NOM-001',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->user->id,
            'agent_id'            => $this->agent->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);
    }
}
