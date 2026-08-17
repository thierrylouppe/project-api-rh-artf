<?php

namespace Tests\Feature;

use App\Enums\StatutDossier;
use App\Models\DossierIntegration;
use App\Models\TypeIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActeAdministratifTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private DossierIntegration $dossier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $type = TypeIntegration::create([
            'nom'                     => 'Recrutement externe',
            'type_acte_administratif' => 'decision_recrutement',
            'necessite_contrat'       => false,
        ]);

        $this->dossier = DossierIntegration::create([
            'reference'           => 'ARTF-INT-TEST-001',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->user->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);
    }

    public function test_enregistrer_acte_en_post_integration_sans_changer_le_statut(): void
    {
        $this->postJson("/api/integration/dossiers/{$this->dossier->id}/generer-acte")
            ->assertCreated()
            ->assertJsonPath('data.acte.type_acte', 'decision_recrutement')
            ->assertJsonPath('data.acte.numero', 'ARTF-REC-' . now()->year . '-0001')
            ->assertJsonPath('data.dossier.statut', 'INTEGRE');
    }

    public function test_second_appel_est_idempotent(): void
    {
        $premier = $this->postJson("/api/integration/dossiers/{$this->dossier->id}/generer-acte");
        $premier->assertCreated();
        $numero = $premier->json('data.acte.numero');

        $this->postJson("/api/integration/dossiers/{$this->dossier->id}/generer-acte")
            ->assertOk()
            ->assertJsonPath('data.acte.numero', $numero)
            ->assertJsonPath('data.dossier.statut', 'INTEGRE');
    }

    public function test_statut_non_autorise_retourne_422(): void
    {
        $this->dossier->update(['statut' => StatutDossier::BROUILLON]);

        $this->postJson("/api/integration/dossiers/{$this->dossier->id}/generer-acte")
            ->assertStatus(422);
    }
}
