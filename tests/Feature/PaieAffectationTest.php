<?php

namespace Tests\Feature;

use App\Enums\CodePaieElement;
use App\Models\Agent;
use App\Models\Fonction;
use App\Models\PaieElement;
use App\Models\User;
use Database\Seeders\PaieElementSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaieAffectationTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private Fonction $dd;

    private Fonction $cb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, PaieElementSeeder::class]);

        $this->dd = Fonction::create(['nom' => 'Directeur Départemental', 'sigle' => 'DD']);
        $this->cb = Fonction::create(['nom' => 'Chef de bureau', 'sigle' => 'CB']);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($this->rh, ['*']);
    }

    public function test_crud_affectation(): void
    {
        $agent = $this->creerAgent('actif', $this->cb);
        $element = $this->element(CodePaieElement::PRIME_VESTIMENTAIRE);

        $created = $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $element->id,
            'montant' => 15000,
            'date_debut' => '2026-01-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant', 15000)
            ->assertJsonPath('data.element.code', 'prime_vestimentaire')
            ->assertJsonPath('data.agent.id', $agent->id)
            ->assertJsonPath('data.active', true);

        $id = $created->json('data.id');

        $this->putJson("/api/paie/affectations/{$id}", [
            'montant' => 18000,
            'date_fin' => '2026-06-30',
        ])
            ->assertOk()
            ->assertJsonPath('data.montant', 18000)
            ->assertJsonPath('data.date_fin', '2026-06-30');

        $this->getJson("/api/paie/agents/{$agent->id}/affectations")
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->getJson('/api/paie/affectations?actives=1')
            ->assertOk();

        $this->deleteJson("/api/paie/affectations/{$id}")
            ->assertOk();
    }

    public function test_representation_reservee_au_dd(): void
    {
        $element = $this->element(CodePaieElement::PRIME_REPRESENTATION);
        $payload = [
            'paie_element_id' => $element->id,
            'montant' => 50000,
            'date_debut' => '2026-01-01',
        ];

        $this->postJson('/api/paie/affectations', $payload + [
            'agent_id' => $this->creerAgent('actif', $this->cb)->id,
        ])->assertStatus(422);

        $this->postJson('/api/paie/affectations', $payload + [
            'agent_id' => $this->creerAgent('actif', $this->dd)->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.element.code', 'prime_representation');
    }

    public function test_chevauchement_refuse(): void
    {
        $agent = $this->creerAgent('actif', $this->cb);
        $element = $this->element(CodePaieElement::INDEMNITE_TRANSPORT);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $element->id,
            'montant' => 20000,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-06-30',
        ])->assertCreated();

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $element->id,
            'montant' => 20000,
            'date_debut' => '2026-06-01',
        ])->assertStatus(422);
    }

    public function test_stagiaire_transport_uniquement(): void
    {
        $stagiaire = $this->creerAgent('stagiaire');
        $titulaire = $this->creerAgent('actif', $this->cb);
        $transport = $this->element(CodePaieElement::PRIME_TRANSPORT_STAGIAIRE);
        $responsabilite = $this->element(CodePaieElement::PRIME_RESPONSABILITE);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $stagiaire->id,
            'paie_element_id' => $transport->id,
            'montant' => 10000,
            'date_debut' => '2026-01-01',
        ])->assertCreated();

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $titulaire->id,
            'paie_element_id' => $transport->id,
            'montant' => 10000,
            'date_debut' => '2026-01-01',
        ])->assertStatus(422);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $stagiaire->id,
            'paie_element_id' => $responsabilite->id,
            'montant' => 30000,
            'date_debut' => '2026-01-01',
        ])->assertStatus(422);
    }

    public function test_element_auto_et_montant_manquant(): void
    {
        $agent = $this->creerAgent('actif', $this->cb);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $this->element(CodePaieElement::PRIME_ANCIENNETE)->id,
            'date_debut' => '2026-01-01',
        ])->assertStatus(422);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $this->element(CodePaieElement::PRIME_VESTIMENTAIRE)->id,
            'date_debut' => '2026-01-01',
        ])->assertStatus(422);
    }

    public function test_mission_locale_plafond_15_jours(): void
    {
        $agent = $this->creerAgent('actif', $this->cb);
        $element = $this->element(CodePaieElement::INDEMNITE_MISSION_LOCALE);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $element->id,
            'quantite' => 16,
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-16',
        ])->assertStatus(422);

        $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $element->id,
            'quantite' => 16,
            'prolongation_dg' => true,
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-16',
        ])->assertCreated();
    }

    public function test_interdit_sans_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/paie/affectations')->assertForbidden();
    }

    private function element(CodePaieElement $code): PaieElement
    {
        return PaieElement::query()->where('code', $code->value)->firstOrFail();
    }

    private function creerAgent(string $statut, ?Fonction $fonction = null): Agent
    {
        return Agent::create([
            'nom' => 'Test',
            'prenom' => 'Agent',
            'date_naissance' => '1990-01-01',
            'genre' => 'M',
            'statut' => $statut,
            'date_prise_service' => '2024-01-01',
            'fonction_id' => $fonction?->id,
        ]);
    }
}
