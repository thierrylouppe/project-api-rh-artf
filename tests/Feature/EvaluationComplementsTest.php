<?php

namespace Tests\Feature;

use App\Enums\StatutAffectation;
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

class EvaluationComplementsTest extends TestCase
{
    use RefreshDatabase;

    private User $rhUser;
    private User $chefUser;
    private User $agentUser;
    private Agent $chef;
    private Agent $agent;
    private Direction $direction;
    private QuestionEvaluation $q1;
    private QuestionEvaluation $q2;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['consulter-evaluations', 'creer-evaluations', 'valider-evaluations'] as $perm) {
            Permission::findOrCreate($perm, 'api');
        }

        Role::findOrCreate('rh', 'api');
        Role::findOrCreate('chef-service', 'api');
        Role::findOrCreate('agent', 'api');

        $this->chef  = $this->creerAgent('Marie', 'Chef');
        $this->agent = $this->creerAgent('Jean', 'Agent');

        $this->rhUser = User::factory()->create();
        $this->rhUser->givePermissionTo(['consulter-evaluations', 'creer-evaluations', 'valider-evaluations']);
        $this->rhUser->assignRole('rh');

        $this->chefUser = User::factory()->create(['agent_id' => $this->chef->id]);
        $this->chefUser->givePermissionTo(['consulter-evaluations', 'valider-evaluations']);
        $this->chefUser->assignRole('chef-service');

        $this->agentUser = User::factory()->create(['agent_id' => $this->agent->id]);
        $this->agentUser->givePermissionTo(['consulter-evaluations']);
        $this->agentUser->assignRole('agent');

        $localite  = Localite::create(['nom' => 'Brazzaville']);
        $admin     = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $this->direction = Direction::create(['nom' => 'DRHL', 'administration_id' => $admin->id]);

        Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Direction::class,
            'structurable_id'           => $this->direction->id,
            'superieur_hierarchique_id' => $this->chef->id,
            'date_affectation'          => '2024-01-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->rhUser->id,
        ]);

        $this->q1 = QuestionEvaluation::create([
            'libelle' => 'Critère A', 'type_critere' => 'competence_pro',
            'bareme_max' => 10.0, 'ordre' => 1, 'actif' => true,
        ]);
        $this->q2 = QuestionEvaluation::create([
            'libelle' => 'Critère B', 'type_critere' => 'assiduite',
            'bareme_max' => 10.0, 'ordre' => 2, 'actif' => true,
        ]);

        $this->agent->update(['date_prise_service' => '2024-01-01']);
    }

    public function test_generation_fiche_renseigne_affectation_de_notation(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $aff     = Affectation::where('agent_id', $this->agent->id)->where('statut', StatutAffectation::ACTIVE)->first();

        $this->assertSame($this->chef->id, $fiche->superieur_id);
        $this->assertSame($aff->id, $fiche->affectation_notation_id);
    }

    public function test_mutation_notateur_est_le_poste_le_plus_long(): void
    {
        $chefB = $this->creerAgent('Paul', 'ChefB');
        $dirB  = Direction::create([
            'nom'               => 'DSI',
            'administration_id' => $this->direction->administration_id,
        ]);

        $ancienne = Affectation::where('agent_id', $this->agent->id)->first();
        $ancienne->update([
            'statut'   => StatutAffectation::TERMINEE,
            'date_fin' => '2025-12-31',
        ]);

        Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Direction::class,
            'structurable_id'           => $dirB->id,
            'superieur_hierarchique_id' => $chefB->id,
            'date_affectation'          => '2026-01-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->rhUser->id,
        ]);

        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->assertSame($this->chef->id, $fiche->superieur_id, 'Le poste le plus long (2024-09 → 2025-12) doit primer.');
        $this->assertSame($ancienne->id, $fiche->affectation_notation_id);
    }

    public function test_egalite_de_duree_prend_laffectation_la_plus_recente(): void
    {
        $chefB = $this->creerAgent('Paul', 'ChefB');
        $dirB  = Direction::create([
            'nom'               => 'DSI',
            'administration_id' => $this->direction->administration_id,
        ]);

        $ancienne = Affectation::where('agent_id', $this->agent->id)->first();
        $ancienne->update([
            'date_affectation' => '2024-09-01',
            'date_fin'         => '2025-08-31',
            'statut'           => StatutAffectation::TERMINEE,
        ]);

        $recente = Affectation::create([
            'agent_id'                  => $this->agent->id,
            'structurable_type'         => Direction::class,
            'structurable_id'           => $dirB->id,
            'superieur_hierarchique_id' => $chefB->id,
            'date_affectation'          => '2025-09-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->rhUser->id,
        ]);

        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->assertSame($chefB->id, $fiche->superieur_id);
        $this->assertSame($recente->id, $fiche->affectation_notation_id);
    }

    public function test_affectation_sans_n1_pas_de_fiche(): void
    {
        Affectation::where('agent_id', $this->agent->id)->update([
            'superieur_hierarchique_id' => null,
        ]);

        Sanctum::actingAs($this->rhUser);
        $sessionId = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseMissing('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);

        $this->getJson("/api/avancements/sessions/{$sessionId}/sans-superieur")
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->agent->id);
    }

    public function test_agent_son_propre_n1_n_a_pas_de_fiche(): void
    {
        Affectation::where('agent_id', $this->agent->id)->update([
            'superieur_hierarchique_id' => $this->agent->id,
        ]);

        Sanctum::actingAs($this->rhUser);
        $sessionId = $this->postJson('/api/avancements/sessions', [
            'debut_session' => '2026-09-01',
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseMissing('evaluations', [
            'session_id' => $sessionId,
            'agent_id'   => $this->agent->id,
        ]);

        $this->getJson("/api/avancements/sessions/{$sessionId}/sans-superieur")
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->agent->id);
    }

    public function test_valider_rh_inscrit_au_tableau_par_defaut(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->finaliserFiche($fiche);

        $fiche->refresh();
        $this->assertTrue($fiche->inscrit_tableau);

        Sanctum::actingAs($this->rhUser);
        $this->getJson("/api/avancements/sessions/{$session->id}/tableau")
            ->assertOk()
            ->assertJsonPath('data.0.id', $fiche->id)
            ->assertJsonPath('data.0.inscrit_tableau', true)
            ->assertJsonPath('data.0.prochaine_etape', 'commission_preparatoire');
    }

    public function test_retirer_du_tableau_bloque_la_decision_commission(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/retirer-tableau")
            ->assertOk()
            ->assertJsonPath('data.inscrit_tableau', false)
            ->assertJsonPath('data.prochaine_etape', 'inscrire_tableau');

        $this->getJson("/api/avancements/sessions/{$session->id}/tableau")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()->json('data.id');

        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/decider", [
            'evaluation_id'    => $fiche->id,
            'decision'         => 'favorable',
            'nombre_echelons'  => 1,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.inscrit_tableau.0', fn ($m) => str_contains($m, 'tableau'));
    }

    public function test_inscrire_apres_cloture_commission_avancement_est_refuse(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/retirer-tableau")->assertOk();

        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();
        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/cloturer")->assertOk();

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/inscrire-tableau")
            ->assertUnprocessable()
            ->assertJsonPath('errors.commission_avancement.0', fn ($m) => str_contains($m, 'clôturée'));
    }

    public function test_fiche_pdf_refusee_avant_signature_agent(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->noterComplet($fiche);

        Sanctum::actingAs($this->rhUser);
        $this->get("/api/avancements/evaluations/{$fiche->id}/fiche-pdf")
            ->assertUnprocessable();
    }

    public function test_fiche_pdf_disponible_apres_signature_agent(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->menerJusquaSigneeEvalue($fiche);

        Sanctum::actingAs($this->agentUser);
        $response = $this->get("/api/avancements/evaluations/{$fiche->id}/fiche-pdf");
        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('content-type') ?? '');
    }

    public function test_fiche_pdf_interdite_a_un_tiers(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->menerJusquaSigneeEvalue($fiche);

        $tiers = User::factory()->create();
        $tiers->givePermissionTo(['consulter-evaluations']);
        $tiers->assignRole('agent');

        Sanctum::actingAs($tiers);
        $this->get("/api/avancements/evaluations/{$fiche->id}/fiche-pdf")
            ->assertForbidden();
    }

    public function test_synthese_pdf_apres_cloture_preparatoire(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);
        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);
        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');

        $this->get("/api/avancements/commissions-preparatoires/{$prepId}/synthese-pdf")
            ->assertUnprocessable();

        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        $ok = $this->get("/api/avancements/commissions-preparatoires/{$prepId}/synthese-pdf");
        $ok->assertOk();
        $this->assertStringContainsString('pdf', $ok->headers->get('content-type') ?? '');

        Sanctum::actingAs($this->agentUser);
        $this->get("/api/avancements/commissions-preparatoires/{$prepId}/synthese-pdf")
            ->assertForbidden();
    }

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
            'question_id' => $this->q1->id, 'note_obtenue' => 8.0,
        ])->assertOk();
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id' => $this->q2->id, 'note_obtenue' => 7.0,
        ])->assertOk();
    }

    private function menerJusquaSigneeEvalue(Evaluation $fiche): void
    {
        $this->noterComplet($fiche);
        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Agent rigoureux, atteint l\'ensemble de ses objectifs.',
        ])->assertOk();
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")->assertOk();
    }

    private function signerChaineAvis(Evaluation $fiche): void
    {
        Sanctum::actingAs($this->rhUser);
        $niveauxRes = $this->getJson("/api/avancements/evaluations/{$fiche->id}/niveaux-requis")
            ->assertOk()->json('data');

        foreach ($niveauxRes as $item) {
            $res = $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-hierarchiques", [
                'niveau'   => $item['niveau'],
                'avis'     => 'Avis conforme. Agent sérieux et compétent.',
                'approuve' => true,
            ])->assertStatus(201);
            $this->postJson("/api/avancements/avis-hierarchiques/{$res->json('data.id')}/signer")->assertOk();
        }
    }

    private function finaliserFiche(Evaluation $fiche): void
    {
        $this->menerJusquaSigneeEvalue($fiche);
        $this->signerChaineAvis($fiche);
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")->assertOk();
        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/valider-rh", ['conforme' => true])->assertOk();
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
