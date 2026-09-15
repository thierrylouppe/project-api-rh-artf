<?php

namespace Tests\Feature;

use App\Enums\ModaliteFormation;
use App\Enums\StatutConventionStage;
use App\Enums\StatutDossier;
use App\Enums\TypeActionFormation;
use App\Enums\TypeStage;
use App\Models\Agent;
use App\Models\ConventionStage;
use App\Models\Diplome;
use App\Models\DossierIntegration;
use App\Models\Echelon;
use App\Models\TypeIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormationsTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach ([
            'consulter-formations',
            'gerer-formations',
            'creer-recrutement',
        ] as $name) {
            Permission::findOrCreate($name, 'api');
        }

        Role::findOrCreate('rh', 'api');

        $this->agent = Agent::create([
            'nom' => 'Mabiala',
            'prenom' => 'Jean',
            'date_naissance' => '1985-01-01',
            'genre' => 'M',
            'statut' => 'actif',
            'date_prise_service' => now()->subYears(4)->toDateString(),
        ]);

        $this->rh = User::factory()->create();
        $this->rh->givePermissionTo(['consulter-formations', 'gerer-formations', 'creer-recrutement']);
        $this->rh->assignRole('rh');

        Sanctum::actingAs($this->rh);
    }

    public function test_catalogue_et_plafond_duree_perfectionnement(): void
    {
        $created = $this->postJson('/api/formations/catalogue', [
            'titre' => 'Séminaire régulation',
            'type_action' => TypeActionFormation::SEMINAIRE->value,
            'modalite' => ModaliteFormation::INTERNE->value,
            'duree_jours' => 5,
            'organisme' => 'CAMRTF',
        ])
            ->assertCreated()
            ->assertJsonPath('data.type_action', 'seminaire');

        $this->postJson('/api/formations/catalogue', [
            'titre' => 'Recyclage trop long',
            'type_action' => TypeActionFormation::PERFECTIONNEMENT->value,
            'duree_jours' => 400,
        ])->assertStatus(422);

        $id = $created->json('data.id');
        $this->putJson("/api/formations/catalogue/{$id}", ['duree_jours' => 3])
            ->assertOk()
            ->assertJsonPath('data.duree_jours', 3);
    }

    public function test_plan_annuel_workflow_et_inscriptions(): void
    {
        $formationId = $this->postJson('/api/formations/catalogue', [
            'titre' => 'Perfectionnement paie',
            'type_action' => TypeActionFormation::PERFECTIONNEMENT->value,
            'duree_jours' => 10,
        ])->assertCreated()->json('data.id');

        $planId = $this->postJson('/api/formations/plans', [
            'annee' => 2026,
            'titre' => 'Plan 2026',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/formations/plans/{$planId}/valider")->assertStatus(422);

        $this->postJson("/api/formations/plans/{$planId}/lignes", [
            'formation_id' => $formationId,
            'places_prevues' => 12,
        ])->assertCreated();

        $this->postJson("/api/formations/plans/{$planId}/valider")
            ->assertOk()
            ->assertJsonPath('data.statut', 'valide');

        $this->postJson("/api/formations/plans/{$planId}/lignes", [
            'formation_id' => $formationId,
        ])->assertStatus(422);

        $inscription = $this->postJson('/api/formations/inscriptions', [
            'agent_id' => $this->agent->id,
            'formation_id' => $formationId,
            'plan_id' => $planId,
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-03-15',
        ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'inscrite');

        $id = $inscription->json('data.id');

        $this->postJson("/api/formations/inscriptions/{$id}/cloturer", [
            'rapport_remis' => true,
        ])->assertOk();

        $this->postJson("/api/formations/inscriptions/{$id}/confirmer-presence")->assertStatus(422);

        $this->postJson("/api/formations/plans/{$planId}/executer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'execute');
    }

    public function test_anciennete_insuffisante_refusee(): void
    {
        $junior = Agent::create([
            'nom' => 'Junior',
            'prenom' => 'Paul',
            'date_naissance' => '1998-01-01',
            'genre' => 'M',
            'statut' => 'actif',
            'date_prise_service' => now()->subYear()->toDateString(),
        ]);

        $formationId = $this->postJson('/api/formations/catalogue', [
            'titre' => 'Qualification métiers',
            'type_action' => TypeActionFormation::QUALIFICATION->value,
            'duree_jours' => 30,
        ])->json('data.id');

        $this->postJson('/api/formations/inscriptions', [
            'agent_id' => $junior->id,
            'formation_id' => $formationId,
        ])->assertStatus(422);
    }

    public function test_admission_sur_titre_exige_dernier_echelon(): void
    {
        $formationId = $this->postJson('/api/formations/catalogue', [
            'titre' => 'École spécialisée',
            'type_action' => TypeActionFormation::ECOLE->value,
            'duree_jours' => 60,
        ])->json('data.id');

        $this->postJson('/api/formations/inscriptions', [
            'agent_id' => $this->agent->id,
            'formation_id' => $formationId,
            'admission_sur_titre' => true,
        ])->assertStatus(422);

        $echelon = Echelon::create(['nom' => 'Échelon 12', 'numero' => 12]);
        $this->agent->update(['echelon_id' => $echelon->id]);

        $this->postJson('/api/formations/inscriptions', [
            'agent_id' => $this->agent->id,
            'formation_id' => $formationId,
            'admission_sur_titre' => true,
        ])->assertCreated();
    }

    public function test_certification_et_piece(): void
    {
        $formationId = $this->postJson('/api/formations/catalogue', [
            'titre' => 'Séminaire PMP',
            'type_action' => TypeActionFormation::SEMINAIRE->value,
            'duree_jours' => 3,
        ])->json('data.id');

        $diplome = Diplome::create(['nom' => 'Master test']);

        $this->postJson('/api/formations/certifications', [
            'agent_id' => $this->agent->id,
            'formation_id' => $formationId,
            'diplome_id' => $diplome->id,
            'date_obtention' => now()->toDateString(),
            'reference' => 'CERT-1',
            'fichier' => UploadedFile::fake()->create('diplome.pdf', 20, 'application/pdf'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.has_fichier', true)
            ->assertJsonPath('data.reference', 'CERT-1');

        $this->getJson("/api/formations/agents/{$this->agent->id}/certifications")
            ->assertOk()
            ->assertJsonPath('data.0.diplome.nom', 'Master test');
    }

    public function test_convertir_stagiaire_ouvre_dossier_recrutement(): void
    {
        TypeIntegration::create(['nom' => 'Recrutement externe']);

        $stagiaire = Agent::create([
            'nom' => 'Stagiaire',
            'prenom' => 'Léa',
            'date_naissance' => '2000-01-01',
            'genre' => 'F',
            'statut' => 'inactif',
        ]);

        $typeStage = TypeIntegration::create(['nom' => 'Stage professionnel']);
        $dossierStage = DossierIntegration::create([
            'reference' => 'ARTF-INT-2026-000001',
            'type_integration_id' => $typeStage->id,
            'demandeur_id' => $this->rh->id,
            'agent_id' => $stagiaire->id,
            'date_demande' => now()->toDateString(),
            'statut' => StatutDossier::INTEGRE,
        ]);

        $enCours = ConventionStage::create([
            'agent_id' => $stagiaire->id,
            'dossier_integration_id' => $dossierStage->id,
            'type_stage' => TypeStage::PROFESSIONNEL,
            'etablissement' => 'Université',
            'date_debut' => now()->subMonths(6)->toDateString(),
            'date_fin' => now()->subDay()->toDateString(),
            'statut_stage' => StatutConventionStage::EN_COURS,
        ]);

        $this->postJson("/api/integration/stages/{$enCours->id}/convertir-agent")
            ->assertStatus(422);

        $enCours->update(['statut_stage' => StatutConventionStage::TERMINE]);

        $this->postJson("/api/integration/stages/{$enCours->id}/convertir-agent")
            ->assertCreated()
            ->assertJsonPath('data.agent_id', $stagiaire->id)
            ->assertJsonPath('data.type_integration.nom', 'Recrutement externe')
            ->assertJsonPath('data.statut', StatutDossier::BROUILLON->value);

        $this->postJson("/api/integration/stages/{$enCours->id}/convertir-agent")
            ->assertStatus(422);
    }
}
