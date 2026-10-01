<?php

namespace Tests\Feature;

use App\Enums\StatutAffectation;
use App\Enums\StatutDossier;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\DossierIntegration;
use App\Models\Localite;
use App\Models\LotAffectation;
use App\Models\Nomination;
use App\Models\Service;
use App\Models\TypeIntegration;
use App\Models\User;
use App\Models\ValidationWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AffectationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Agent $agent;

    private Agent $superieur;

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

        $this->superieur = $this->creerAgent('Chef', 'Structure');
        $this->agent     = $this->creerAgent('Jean', 'Agent');

        Nomination::create([
            'agent_id'          => $this->superieur->id,
            'poste'             => 'Directeur Central',
            'structurable_type' => Direction::class,
            'structurable_id'   => $this->direction->id,
            'date_debut'        => '2026-01-01',
            'statut'            => 'active',
            'created_by'        => $this->user->id,
        ]);
    }

    public function test_creer_unitaire_via_carriere_et_alias_integration(): void
    {
        $payload = $this->payloadAffectation($this->agent);

        $this->postJson('/api/carriere/affectations', $payload)
            ->assertCreated()
            ->assertJsonPath('data.statut', StatutAffectation::EN_ATTENTE_VALIDATION->value)
            ->assertJsonPath('data.superieur_hierarchique_id', $this->superieur->id);

        $this->assertSame(1, $this->user->notifications()->count());
        $this->assertSame('creee', $this->user->notifications()->first()->data['action']);
        $this->assertSame('affectation', $this->user->notifications()->first()->data['domaine']);

        $this->postJson('/api/integration/affectations', [
            ...$payload,
            'agent_id' => $this->creerAgent('Alias', 'Fe')->id,
        ])->assertCreated();
    }

    public function test_structure_inexistante_retourne_422(): void
    {
        $this->postJson('/api/carriere/affectations', [
            ...$this->payloadAffectation($this->agent),
            'structurable_id' => 99999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['structurable_id']);
    }

    public function test_affectation_groupee_un_circuit_et_activation_globale(): void
    {
        $autre = $this->creerAgent('Marie', 'Duo');

        $response = $this->postJson('/api/carriere/affectations/groupee', [
            'date_affectation' => '2026-07-01',
            'motif'            => 'Réorganisation',
            'agents'           => [
                [
                    'agent_id'          => $this->agent->id,
                    'structurable_type' => Bureau::class,
                    'structurable_id'   => $this->bureau->id,
                ],
                [
                    'agent_id'          => $autre->id,
                    'structurable_type' => Service::class,
                    'structurable_id'   => $this->service->id,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.statut', StatutAffectation::EN_ATTENTE_VALIDATION->value);

        $lotId = $response->json('data.id');
        $this->assertCount(2, $response->json('data.affectations'));
        $this->assertSame(0, ValidationWorkflow::query()->where('validable_type', Affectation::class)->count());
        $this->assertSame(5, ValidationWorkflow::query()->where('validable_type', LotAffectation::class)->where('validable_id', $lotId)->count());

        $ligneId = $response->json('data.affectations.0.id');
        $this->postJson("/api/carriere/affectations/{$ligneId}/activer")->assertStatus(422);

        $validations = ValidationWorkflow::query()
            ->where('validable_type', LotAffectation::class)
            ->where('validable_id', $lotId)
            ->orderBy('ordre')
            ->get();

        foreach ($validations as $validation) {
            $this->postJson("/api/integration/validations/{$validation->id}/approuver", [
                'commentaire' => 'OK lot '.$validation->ordre,
            ])->assertOk();
        }

        $this->assertSame(StatutAffectation::APPROUVEE, LotAffectation::find($lotId)->statut);
        $this->assertTrue(Affectation::where('lot_affectation_id', $lotId)->get()->every(
            fn (Affectation $a) => $a->statut === StatutAffectation::APPROUVEE
        ));

        $this->postJson("/api/carriere/affectations/lots/{$lotId}/activer")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutAffectation::ACTIVE->value);

        $this->assertTrue(Affectation::where('lot_affectation_id', $lotId)->get()->every(
            fn (Affectation $a) => $a->statut === StatutAffectation::ACTIVE
        ));

        $this->get("/api/carriere/affectations/lots/{$lotId}/acte")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_lot_autorise_meme_structure_et_refuse_un_seul_agent(): void
    {
        $autre = $this->creerAgent('Marc', 'Boukaka');

        $this->postJson('/api/carriere/affectations/groupee', [
            'date_affectation' => '2026-07-01',
            'agents'           => [
                [
                    'agent_id'          => $this->agent->id,
                    'structurable_type' => Bureau::class,
                    'structurable_id'   => $this->bureau->id,
                ],
            ],
        ])->assertStatus(422);

        $this->postJson('/api/carriere/affectations/groupee', [
            'date_affectation' => '2026-07-01',
            'agents'           => [
                [
                    'agent_id'          => $this->agent->id,
                    'structurable_type' => Bureau::class,
                    'structurable_id'   => $this->bureau->id,
                ],
                [
                    'agent_id'          => $autre->id,
                    'structurable_type' => Bureau::class,
                    'structurable_id'   => $this->bureau->id,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.total', 2);
    }

    public function test_circuit_dernier_niveau_passe_en_approuvee(): void
    {
        $affectation = $this->creerAffectation($this->agent);

        $validations = ValidationWorkflow::query()
            ->where('validable_type', Affectation::class)
            ->where('validable_id', $affectation->id)
            ->orderBy('ordre')
            ->get();

        $this->assertCount(5, $validations);

        foreach ($validations as $validation) {
            $this->postJson("/api/integration/validations/{$validation->id}/approuver", [
                'commentaire' => 'Approuvé niveau ' . $validation->ordre,
            ])->assertOk();
        }

        $this->assertSame(
            StatutAffectation::APPROUVEE,
            $affectation->fresh()->statut
        );
    }

    public function test_activer_cloture_ancienne_active_sans_changer_le_dossier(): void
    {
        $ancienne = $this->creerAffectation($this->agent, StatutAffectation::ACTIVE);
        $nouvelle = $this->creerAffectation($this->agent, StatutAffectation::APPROUVEE);

        $dossier = $this->creerDossierIntegre();

        $this->postJson("/api/carriere/affectations/{$nouvelle->id}/activer", [
            'dossier_integration_id' => $dossier->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutAffectation::ACTIVE->value);

        $this->assertSame(StatutAffectation::TERMINEE, $ancienne->fresh()->statut);
        $this->assertSame(StatutDossier::INTEGRE, $dossier->fresh()->statut);
    }

    public function test_activer_depuis_en_attente_retourne_422(): void
    {
        $affectation = $this->creerAffectation($this->agent);

        $this->postJson("/api/carriere/affectations/{$affectation->id}/activer")
            ->assertStatus(422);
    }

    public function test_rejeter_et_terminer(): void
    {
        $aRejeter = $this->creerAffectation($this->agent);
        $this->postJson("/api/carriere/affectations/{$aRejeter->id}/rejeter", [
            'commentaire' => 'Structure inexistante à la date',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutAffectation::REJETEE->value);

        $active = $this->creerAffectation($this->agent, StatutAffectation::ACTIVE);
        $this->postJson("/api/carriere/affectations/{$active->id}/terminer", [
            'date_fin' => now()->toDateString(),
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutAffectation::TERMINEE->value);

        $this->postJson("/api/carriere/affectations/{$active->id}/terminer")
            ->assertStatus(422);
    }

    public function test_liste_par_agent(): void
    {
        $this->creerAffectation($this->agent);

        $this->getJson("/api/carriere/agents/{$this->agent->id}/affectations")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.agent_id', $this->agent->id)
            ->assertJsonPath('data.0.agent.nom', $this->agent->nom)
            ->assertJsonPath('data.0.agent.prenom', $this->agent->prenom)
            ->assertJsonPath('data.0.agent.nom_complet', $this->agent->nom_complet);
    }

    public function test_index_inclut_identite_agent(): void
    {
        $this->creerAffectation($this->agent);

        $this->getJson('/api/carriere/affectations')
            ->assertOk()
            ->assertJsonPath('data.0.agent_id', $this->agent->id)
            ->assertJsonPath('data.0.agent.id', $this->agent->id)
            ->assertJsonPath('data.0.agent.nom', $this->agent->nom)
            ->assertJsonPath('data.0.agent.prenom', $this->agent->prenom)
            ->assertJsonPath('data.0.agent.nom_complet', $this->agent->nom_complet);
    }

    private function payloadAffectation(Agent $agent): array
    {
        return [
            'agent_id'          => $agent->id,
            'structurable_type' => Bureau::class,
            'structurable_id'   => $this->bureau->id,
            'date_affectation'  => '2026-07-01',
            'motif'             => 'Première affectation',
        ];
    }

    private function creerAffectation(Agent $agent, ?StatutAffectation $statut = null): Affectation
    {
        $response = $this->postJson('/api/carriere/affectations', $this->payloadAffectation($agent));
        $response->assertCreated();

        $affectation = Affectation::findOrFail($response->json('data.id'));

        if ($statut !== null) {
            $affectation->update(['statut' => $statut]);
        }

        return $affectation->fresh();
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
            'reference'           => 'DOS-TEST-001',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->user->id,
            'agent_id'            => $this->agent->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);
    }
}
