<?php

namespace Tests\Feature;

use App\Enums\StatutAffectation;
use App\Enums\StatutEvaluation;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Direction;
use App\Models\Echelon;
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

/**
 * Tests Feature Phase 5 — Satellites.
 *
 * Couvre :
 *   - 5.6 Stats session
 *   - 5.1 Bonification stage art. 71
 *   - 5.2 Avancement exceptionnel art. 72
 *   - 5.3 Connaissances complémentaires
 */
class Phase5EvaluationTest extends TestCase
{
    use RefreshDatabase;

    private User $rhUser;
    private User $chefUser;
    private User $agentUser;
    private Agent $chef;
    private Agent $agent;

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

        $this->chef  = $this->creerAgent('Marie', 'Chef');
        $this->agent = $this->creerAgent('Jean', 'Agent');

        $this->rhUser = User::factory()->create();
        $this->rhUser->givePermissionTo(['consulter-evaluations', 'creer-evaluations', 'valider-evaluations']);

        $this->chefUser = User::factory()->create(['agent_id' => $this->chef->id]);
        $this->chefUser->givePermissionTo(['consulter-evaluations', 'valider-evaluations']);

        $this->agentUser = User::factory()->create(['agent_id' => $this->agent->id]);
        $this->agentUser->givePermissionTo(['consulter-evaluations']);

        $localite  = Localite::create(['nom' => 'Brazzaville']);
        $admin     = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $direction = Direction::create(['nom' => 'DRHL', 'administration_id' => $admin->id]);

        Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Direction::class,
            'structurable_id'           => $direction->id,
            'superieur_hierarchique_id' => $this->chef->id,
            'date_affectation'          => '2024-01-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->rhUser->id,
        ]);

        QuestionEvaluation::create(['libelle' => 'Q1', 'type_critere' => 'competence_pro', 'bareme_max' => 10.0, 'ordre' => 1, 'actif' => true]);
        QuestionEvaluation::create(['libelle' => 'Q2', 'type_critere' => 'assiduite',      'bareme_max' => 10.0, 'ordre' => 2, 'actif' => true]);
    }

    // ================================================================
    // 5.6 — Stats session
    // ================================================================

    public function test_stats_session_retourne_compteurs(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->rhUser);

        $data = $this->getJson("/api/avancements/sessions/{$session->id}/stats")
            ->assertOk()
            ->json('data');

        $this->assertSame($session->id, $data['session_id']);
        $this->assertSame(1, $data['total']);
        $this->assertArrayHasKey('en_attente', $data['par_statut']);
        $this->assertNull($data['moyenne']); // pas encore notée
    }

    public function test_stats_session_calcule_moyenne_et_mentions(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // Pousser la fiche jusqu'à notée
        Sanctum::actingAs($this->chefUser);
        $qs = QuestionEvaluation::all();
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $qs->first()->id,
            'note_obtenue' => 8.0,
        ])->assertOk();
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $qs->last()->id,
            'note_obtenue' => 8.0,
        ])->assertOk();

        Sanctum::actingAs($this->rhUser);

        $data = $this->getJson("/api/avancements/sessions/{$session->id}/stats")
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(16.0, $data['moyenne'], 0.01); // 8 + 8
        $this->assertNotEmpty($data['mentions']);
    }

    // ================================================================
    // 5.1 — Bonification stage art. 71
    // ================================================================

    public function test_bonification_stage_cycle_complet(): void
    {
        Sanctum::actingAs($this->rhUser);

        // Soumettre (stage > 9 mois)
        $bonifId = $this->postJson('/api/avancements/bonifications-stage', [
            'agent_id'         => $this->agent->id,
            'date_debut_stage' => '2025-01-01',
            'date_fin_stage'   => '2025-11-30',
            'type_document'    => 'certificat',
            'reference_document' => 'CERT-2025-001',
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.nb_echelons', 2)
            ->json('data.id');

        // La liste en attente le contient
        $this->getJson('/api/avancements/bonifications-stage/en-attente')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Approuver
        $this->postJson("/api/avancements/bonifications-stage/{$bonifId}/traiter", [
            'approuver'   => true,
            'commentaire' => 'Stage confirmé.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'approuvee');

        // Double traitement → 422
        $this->postJson("/api/avancements/bonifications-stage/{$bonifId}/traiter", [
            'approuver' => false,
        ])->assertUnprocessable();
    }

    public function test_bonification_stage_refuse_si_moins_9_mois(): void
    {
        Sanctum::actingAs($this->rhUser);

        $this->postJson('/api/avancements/bonifications-stage', [
            'agent_id'         => $this->agent->id,
            'date_debut_stage' => '2025-01-01',
            'date_fin_stage'   => '2025-06-01', // ~5 mois
            'type_document'    => 'attestation',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.duree_mois.0', fn ($m) => str_contains($m, '9 mois'));
    }

    // ================================================================
    // 5.2 — Avancement exceptionnel art. 72
    // ================================================================

    public function test_avancement_exceptionnel_cycle_complet(): void
    {
        Sanctum::actingAs($this->rhUser);

        // Proposer
        $avanId = $this->postJson('/api/avancements/avancements-exceptionnels', [
            'agent_id'    => $this->agent->id,
            'nb_echelons' => 2,
            'motif'       => 'Agent exceptionnel ayant dépassé tous les objectifs de l\'année.',
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'en_attente')
            ->assertJsonPath('data.nb_echelons', 2)
            ->json('data.id');

        // En attente
        $this->getJson('/api/avancements/avancements-exceptionnels/en-attente')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Approuver
        $this->postJson("/api/avancements/avancements-exceptionnels/{$avanId}/traiter", [
            'approuver'   => true,
            'commentaire' => 'Approuvé en commission.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'approuvee');
    }

    public function test_avancement_exceptionnel_valide_1_ou_2_echelons(): void
    {
        Sanctum::actingAs($this->rhUser);

        // 3 échelons → 422
        $this->postJson('/api/avancements/avancements-exceptionnels', [
            'agent_id'    => $this->agent->id,
            'nb_echelons' => 3,
            'motif'       => 'Test invalide pour 3 échelons.',
        ])->assertUnprocessable();

        // 0 échelon → 422
        $this->postJson('/api/avancements/avancements-exceptionnels', [
            'agent_id'    => $this->agent->id,
            'nb_echelons' => 0,
            'motif'       => 'Test invalide pour 0 échelon.',
        ])->assertUnprocessable();
    }

    // ================================================================
    // 5.3 — Connaissances complémentaires
    // ================================================================

    public function test_connaissances_complementaires_crud(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);

        // Ajouter deux besoins
        $connId = $this->postJson("/api/avancements/evaluations/{$fiche->id}/connaissances", [
            'type'        => 'formation',
            'domaine'     => 'Gestion de projet',
            'description' => 'Formation PMP souhaitée.',
            'urgent'      => true,
        ])->assertCreated()
            ->assertJsonPath('data.type', 'formation')
            ->assertJsonPath('data.urgent', true)
            ->json('data.id');

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/connaissances", [
            'type'    => 'certification',
            'domaine' => 'Excel avancé',
        ])->assertCreated();

        // Liste par évaluation
        $this->getJson("/api/avancements/evaluations/{$fiche->id}/connaissances")
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // Supprimer
        $this->deleteJson("/api/avancements/connaissances/{$connId}")->assertOk();

        $this->getJson("/api/avancements/evaluations/{$fiche->id}/connaissances")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function creerAgent(string $prenom, string $nom): Agent
    {
        return Agent::create([
            'nom'              => $nom,
            'prenom'           => $prenom,
            'date_naissance'   => '1985-01-01',
            'genre'            => 'M',
            'date_prise_service' => '2022-01-01',
            'statut'           => 'actif',
        ]);
    }

    private function creerSession(): SessionEvaluation
    {
        Sanctum::actingAs($this->rhUser);
        $response = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated();
        return SessionEvaluation::find($response->json('data.id'));
    }

    private function fichePourAgent(SessionEvaluation $session): Evaluation
    {
        // S'assurer que les fiches existent (générer si nécessaire)
        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/sessions/{$session->id}/generer-fiches");

        return Evaluation::where('session_id', $session->id)
            ->where('agent_id', $this->agent->id)
            ->firstOrFail();
    }
}
