<?php

namespace Tests\Feature;

use App\Enums\StatutDossier;
use App\Models\Agent;
use App\Models\DossierIntegration;
use App\Models\TypeDocument;
use App\Models\TypeIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DossierAgentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->agent = Agent::create([
            'nom'            => 'Mabiala',
            'prenom'         => 'Jean',
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'statut'         => 'actif',
        ]);

        $type = TypeIntegration::create(['nom' => 'Recrutement externe test']);
        DossierIntegration::create([
            'reference'           => 'DOS-TEST-B-001',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->user->id,
            'agent_id'            => $this->agent->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => StatutDossier::INTEGRE->value,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_liste_dossiers_inclut_identite_agent(): void
    {
        $this->getJson('/api/integration/dossiers')
            ->assertOk()
            ->assertJsonPath('data.0.agent_id', $this->agent->id)
            ->assertJsonPath('data.0.agent.id', $this->agent->id)
            ->assertJsonPath('data.0.agent.nom', $this->agent->nom)
            ->assertJsonPath('data.0.agent.prenom', $this->agent->prenom)
            ->assertJsonPath('data.0.agent.nom_complet', $this->agent->nom_complet);
    }

    public function test_fiche_et_upsert_infos_perso_pro_famille_et_contacts(): void
    {
        $this->getJson("/api/personnel/agents/{$this->agent->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->agent->id)
            ->assertJsonPath('data.informations_personnelles', null);

        $this->getJson("/api/personnel/agents/{$this->agent->id}/informations-personnelles")
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->putJson("/api/personnel/agents/{$this->agent->id}/informations-personnelles", [
            'adresse' => '12 av. de la Paix',
            'ville'   => 'Brazzaville',
            'pays'    => 'Congo',
        ])
            ->assertOk()
            ->assertJsonPath('data.ville', 'Brazzaville');

        $this->putJson("/api/personnel/agents/{$this->agent->id}/informations-professionnelles", [
            'specialite'        => 'Droit public',
            'annees_experience' => 5,
        ])->assertOk()->assertJsonPath('data.specialite', 'Droit public');

        $this->putJson("/api/personnel/agents/{$this->agent->id}/situation-familiale", [
            'statut_matrimonial' => 'marie',
            'nb_enfants'         => 2,
        ])->assertOk()->assertJsonPath('data.nb_enfants', 2);

        $id = $this->postJson("/api/personnel/agents/{$this->agent->id}/contacts-urgence", [
            'nom'       => 'Mabiala',
            'prenom'    => 'Marie',
            'telephone' => '066000000',
            'relation'  => 'conjoint',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/personnel/agents/{$this->agent->id}/contacts-urgence")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson("/api/personnel/agents/{$this->agent->id}/contacts-urgence/{$id}")
            ->assertOk();
    }

    public function test_ged_upload_liste_et_suppression(): void
    {
        $type = TypeDocument::create(['nom' => 'CNI', 'obligatoire' => false]);

        $id = $this->post("/api/personnel/agents/{$this->agent->id}/documents", [
            'type_document_id' => $type->id,
            'titre'            => 'Carte nationale',
            'sous_dossier'     => 'identite',
            'fichier'          => UploadedFile::fake()->create('cni.pdf', 40, 'application/pdf'),
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/personnel/agents/{$this->agent->id}/documents")
            ->assertOk()
            ->assertJsonPath('data.0.sous_dossier', 'identite');

        $this->getJson("/api/personnel/agents/{$this->agent->id}/documents/arborescence")
            ->assertOk()
            ->assertJsonPath('data.0.sous_dossier', 'identite');

        $this->get("/api/personnel/agents/{$this->agent->id}/documents/{$id}/fichier")
            ->assertOk();

        $this->deleteJson("/api/personnel/agents/{$this->agent->id}/documents/{$id}")
            ->assertOk();
    }

    public function test_archivage_desactive_le_compte_et_bloque_lecriture(): void
    {
        $compte = User::factory()->create(['agent_id' => $this->agent->id, 'is_active' => true]);

        $this->postJson("/api/personnel/agents/{$this->agent->id}/archiver", ['motif' => 'Départ à la retraite'])
            ->assertOk()
            ->assertJsonPath('data.statut', 'archive');

        $this->assertFalse($compte->fresh()->is_active);

        $this->putJson("/api/personnel/agents/{$this->agent->id}/informations-personnelles", [
            'ville' => 'Pointe-Noire',
        ])->assertStatus(422);

        $this->getJson('/api/personnel/agents')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/personnel/agents?statut=archive')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson("/api/personnel/agents/{$this->agent->id}/desarchiver")
            ->assertOk()
            ->assertJsonPath('data.statut', 'inactif');

        $this->assertTrue($compte->fresh()->is_active);
    }
}
