<?php

namespace Tests\Feature;

use App\Enums\StatutAffectation;
use App\Enums\StatutEvaluation;
use App\Enums\StatutSessionEvaluation;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Direction;
use App\Models\Evaluation;
use App\Models\Localite;
use App\Models\QuestionEvaluation;
use App\Models\SessionEvaluation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $rhUser;
    private User $chefUser;
    private User $agentUser;
    private Agent $chef;
    private Agent $agent;
    private QuestionEvaluation $q1;
    private QuestionEvaluation $q2;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'consulter-evaluations',
            'creer-evaluations',
            'valider-evaluations',
        ] as $perm) {
            Permission::findOrCreate($perm, 'api');
        }

        Role::findOrCreate('rh', 'api');
        Role::findOrCreate('chef-service', 'api');
        Role::findOrCreate('agent', 'api');

        // Agents
        $this->chef  = $this->creerAgent('Marie', 'Chef');
        $this->agent = $this->creerAgent('Jean', 'Agent');

        // Comptes utilisateurs (créés AVANT l'affectation pour que created_by existe)
        $this->rhUser = User::factory()->create();
        $this->rhUser->givePermissionTo(['consulter-evaluations', 'creer-evaluations', 'valider-evaluations']);
        $this->rhUser->assignRole('rh');

        $this->chefUser = User::factory()->create(['agent_id' => $this->chef->id]);
        $this->chefUser->givePermissionTo(['consulter-evaluations', 'valider-evaluations']);
        $this->chefUser->assignRole('chef-service');

        $this->agentUser = User::factory()->create(['agent_id' => $this->agent->id]);
        $this->agentUser->givePermissionTo(['consulter-evaluations']);
        $this->agentUser->assignRole('agent');

        // Structure (nécessaire pour les affectations)
        $localite    = Localite::create(['nom' => 'Brazzaville']);
        $admin       = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $direction   = Direction::create(['nom' => 'DRHL', 'administration_id' => $admin->id]);

        Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Direction::class,
            'structurable_id'           => $direction->id,
            'superieur_hierarchique_id' => $this->chef->id,
            'date_affectation'          => '2024-01-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->rhUser->id,
        ]);

        // 2 questions minimales pour les tests
        $this->q1 = QuestionEvaluation::create([
            'libelle'      => 'Critère A',
            'type_critere' => 'competence_pro',
            'bareme_max'   => 10.0,
            'ordre'        => 1,
            'actif'        => true,
        ]);
        $this->q2 = QuestionEvaluation::create([
            'libelle'      => 'Critère B',
            'type_critere' => 'assiduite',
            'bareme_max'   => 10.0,
            'ordre'        => 2,
            'actif'        => true,
        ]);

        // Ancienneté ≥ 2 ans : date de prise de service par défaut pour les tests
        $this->agent->update(['date_prise_service' => '2024-01-01']);
    }

    // ----------------------------------------------------------------
    // Test 1 : unicité session ouverte
    // ----------------------------------------------------------------

    public function test_impossible_ouvrir_deux_sessions_simultanement(): void
    {
        Sanctum::actingAs($this->rhUser);

        $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated();

        $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-10-01',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.session.0', fn ($msg) => str_contains($msg, 'session'));
    }

    // ----------------------------------------------------------------
    // Test 2 : agents exemptés ne reçoivent pas de fiche
    // ----------------------------------------------------------------

    public function test_agent_stagiaire_ou_detache_n_a_pas_de_fiche(): void
    {
        // Mettre l'agent en statut stagiaire
        $this->agent->update(['statut' => 'stagiaire']);

        Sanctum::actingAs($this->rhUser);

        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        // Aucune fiche créée pour l'agent stagiaire
        $this->assertDatabaseMissing('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);
    }

    // ----------------------------------------------------------------
    // Test 2b : parité d'année de recrutement
    // ----------------------------------------------------------------

    public function test_parité_annee_filtre_agents_non_concernés(): void
    {
        // agent embauché en année paire (2020)
        $this->agent->update(['date_prise_service' => '2020-03-15']);

        Sanctum::actingAs($this->rhUser);

        // Session pour années IMPAIRES → agent 2020 (paire) ne doit PAS avoir de fiche
        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
            'type_annee'    => 'impaire',
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        $this->assertDatabaseMissing('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);
    }

    public function test_parité_annee_inclut_agents_concernés(): void
    {
        // agent embauché en année paire (2020)
        $this->agent->update(['date_prise_service' => '2020-03-15']);

        Sanctum::actingAs($this->rhUser);

        // Session pour années PAIRES → agent 2020 doit avoir une fiche
        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
            'type_annee'    => 'paire',
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        $this->assertDatabaseHas('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);
    }

    // ----------------------------------------------------------------
    // Test 2c : ancienneté minimale 2 ans
    // ----------------------------------------------------------------

    public function test_agent_moins_de_2_ans_n_a_pas_de_fiche(): void
    {
        // agent embauché en 2025 → ancienneté = 1 an en 2026
        $this->agent->update(['date_prise_service' => '2025-01-01']);

        Sanctum::actingAs($this->rhUser);

        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        $this->assertDatabaseMissing('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);
    }

    // ----------------------------------------------------------------
    // Test 2d : semestre d'embauche
    // ----------------------------------------------------------------

    public function test_semestre_embauche_filtre_agents_mauvais_semestre(): void
    {
        // agent embauché en août (S2) → exclure des sessions S1
        $this->agent->update(['date_prise_service' => '2020-08-01']);

        Sanctum::actingAs($this->rhUser);

        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
            'semestre'      => 1,
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        $this->assertDatabaseMissing('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);
    }

    // ----------------------------------------------------------------
    // Test 3 : génération fiches + N+1 auto
    // ----------------------------------------------------------------

    public function test_creation_session_genere_fiches_pour_agents_actifs(): void
    {
        // Ancienneté >= 2 ans requise : embauché en 2024 → 2 ans en 2026
        $this->agent->update(['date_prise_service' => '2024-01-01']);

        Sanctum::actingAs($this->rhUser);

        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        $this->assertDatabaseHas('evaluations', [
            'session_id'   => $sessionId,
            'agent_id'     => $this->agent->id,
            'superieur_id' => $this->chef->id,
            'statut'       => StatutEvaluation::EN_ATTENTE->value,
        ]);
    }

    // ----------------------------------------------------------------
    // Test 4 : workflow notation barème
    // ----------------------------------------------------------------

    public function test_note_superieure_au_bareme_est_rejetee(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $this->q1->id,
            'note_obtenue' => 15.0,  // q1.bareme_max = 10
        ])->assertUnprocessable()
            ->assertJsonPath('errors.note_obtenue.0', fn ($msg) => str_contains($msg, 'barème'));
    }

    public function test_notation_complete_passe_statut_a_notee_avec_mention(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);

        // Note q1
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $this->q1->id,
            'note_obtenue' => 8.0,
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::EN_COURS->value);

        // Note q2 → complétude 100 %
        $response = $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $this->q2->id,
            'note_obtenue' => 7.0,
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::NOTEE->value)
            ->assertJsonPath('data.note_globale', 15)
            ->assertJsonPath('data.mention', 'Très bien');
    }

    // ----------------------------------------------------------------
    // Test 5 : workflow signatures
    // ----------------------------------------------------------------

    public function test_workflow_signature_evaluateur_puis_evalue(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);

        // Notation complète (2 questions)
        $this->noterComplet($fiche);

        // Signature évaluateur
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evaluateur")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::SIGNEE_EVALUATEUR->value);

        // Signature agent
        Sanctum::actingAs($this->agentUser);

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::SIGNEE_EVALUE->value);
    }

    // ----------------------------------------------------------------
    // Test 6 : validation RH
    // ----------------------------------------------------------------

    public function test_rh_peut_finaliser_ou_rejeter_une_fiche(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // Conduire la fiche jusqu'à en_validation_rh
        Sanctum::actingAs($this->chefUser);
        $this->noterComplet($fiche);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evaluateur")->assertOk();

        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")->assertOk();
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")->assertUnprocessable();

        // Envoi en validation RH (via envoyer-en-validation-rh — direct ou via valider-rh)
        // Ici on met la fiche en en_validation_rh directement via update (car Phase 1)
        $fiche->update(['statut' => StatutEvaluation::EN_VALIDATION_RH->value]);

        Sanctum::actingAs($this->rhUser);

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/valider-rh", [
            'conforme'    => true,
            'commentaire' => 'Dossier complet.',
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::FINALISEE->value)
            ->assertJsonPath('data.conforme_rh', true);
    }

    // ----------------------------------------------------------------
    // Test 7 : agent voit ses fiches
    // ----------------------------------------------------------------

    public function test_agent_voit_ses_propres_evaluations(): void
    {
        $session = $this->creerSession();
        $this->fichePourAgent($session);

        Sanctum::actingAs($this->agentUser);

        $this->getJson('/api/avancements/evaluations/agent/mes-evaluations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.agent_id', $this->agent->id);
    }

    // ----------------------------------------------------------------
    // Test 8 : clôture session
    // ----------------------------------------------------------------

    public function test_cloturer_session_empeche_nouvelle_session(): void
    {
        Sanctum::actingAs($this->rhUser);

        $response  = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated();

        $sessionId = $response->json('data.id');

        $this->postJson("/api/avancements/sessions/{$sessionId}/cloturer")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutSessionEvaluation::CLOTUREE->value);

        // Maintenant on peut ouvrir une nouvelle session
        $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-10-01',
        ])->assertCreated();
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function creerSession(string $debut = '2026-09-01'): SessionEvaluation
    {
        Sanctum::actingAs($this->rhUser);
        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => $debut,
        ])->assertCreated();

        return SessionEvaluation::findOrFail($response->json('data.id'));
    }

    private function fichePourAgent(SessionEvaluation $session): Evaluation
    {
        return Evaluation::where('session_id', $session->id)
            ->where('agent_id', $this->agent->id)
            ->firstOrFail();
    }

    private function noterComplet(Evaluation $fiche): void
    {
        Sanctum::actingAs($this->chefUser);

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $this->q1->id,
            'note_obtenue' => 8.0,
        ])->assertOk();

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $this->q2->id,
            'note_obtenue' => 7.0,
        ])->assertOk();
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
}
