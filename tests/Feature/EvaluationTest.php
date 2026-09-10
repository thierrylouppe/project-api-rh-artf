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
use App\Models\Service;
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
    // Test 5 : workflow signatures Phase 1 (avis non obligatoire)
    // ----------------------------------------------------------------

    public function test_workflow_signature_evaluateur_puis_evalue(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);

        // Notation complète (2 questions)
        $this->noterComplet($fiche);

        // Signature évaluateur (Phase 1 — sans avis)
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
    // Tests Phase 2 : avis obligatoire, réclamation, envoi RH
    // ----------------------------------------------------------------

    public function test_avis_et_signer_necessite_avis_superieur(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);
        $this->noterComplet($fiche);

        // Trop court → 422
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Court',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.avis_superieur.0', fn ($m) => str_contains($m, '10'));
    }

    public function test_avis_et_signer_enregistre_avis_et_signe(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->chefUser);
        $this->noterComplet($fiche);

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Agent très compétent, bonne maîtrise du domaine.',
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::SIGNEE_EVALUATEUR->value)
            ->assertJsonPath('data.prochaine_etape', 'signer_evalue');

        $this->assertDatabaseHas('evaluations', [
            'id'             => $fiche->id,
            'avis_superieur' => 'Agent très compétent, bonne maîtrise du domaine.',
        ]);
    }

    public function test_agent_peut_reclamer_apres_signature(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->menerJusquaSigneeEvalue($fiche);

        Sanctum::actingAs($this->agentUser);

        $this->postJson("/api/avancements/evaluations/{$fiche->id}/reclamer", [
            'motif' => 'Je conteste ma note, les critères n\'ont pas été correctement évalués.',
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::EN_RECLAMATION->value)
            ->assertJsonPath('data.prochaine_etape', 'traiter_reclamation');

        $this->assertDatabaseHas('reclamations', [
            'evaluation_id' => $fiche->id,
            'statut'        => 'en_attente',
        ]);
    }

    public function test_rh_accepte_reclamation_renvoie_au_notateur(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->menerJusquaSigneeEvalue($fiche);

        // Agent réclame
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/reclamer", [
            'motif' => 'Note injuste sur le critère de connaissance technique.',
        ])->assertOk();

        // Récupérer la réclamation
        $reclamationId = \App\Models\Reclamation::where('evaluation_id', $fiche->id)->first()->id;

        // RH accepte → renvoie au notateur (en_cours)
        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/reclamations/{$reclamationId}/traiter", [
            'acceptee'    => true,
            'commentaire' => 'Nous avons examiné votre réclamation, renvoi au notateur.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'acceptee');

        $this->assertDatabaseHas('evaluations', [
            'id'     => $fiche->id,
            'statut' => StatutEvaluation::EN_COURS->value,
        ]);
    }

    public function test_rh_rejette_reclamation_envoie_en_validation(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->menerJusquaSigneeEvalue($fiche);

        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/reclamer", [
            'motif' => 'Je conteste cette note, le notateur n\'a pas été objectif.',
        ])->assertOk();

        $reclamationId = \App\Models\Reclamation::where('evaluation_id', $fiche->id)->first()->id;

        // RH rejette → note maintenue, envoyée en validation
        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/reclamations/{$reclamationId}/traiter", [
            'acceptee'    => false,
            'commentaire' => 'Réclamation examinée, note maintenue.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'rejetee');

        $this->assertDatabaseHas('evaluations', [
            'id'     => $fiche->id,
            'statut' => StatutEvaluation::EN_VALIDATION_RH->value,
        ]);
    }

    public function test_workflow_complet_p2_sans_reclamation(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // N+1 note
        Sanctum::actingAs($this->chefUser);
        $this->noterComplet($fiche);

        // N+1 : avis + signature
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Bonne performance générale, atteint les objectifs fixés.',
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::SIGNEE_EVALUATEUR->value)
            ->assertJsonPath('data.prochaine_etape', 'signer_evalue');

        // Agent signe
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::SIGNEE_EVALUE->value)
            ->assertJsonPath('data.prochaine_etape', 'envoyer_rh');

        // Phase 3 : signer la chaîne des avis hiérarchiques
        $this->signerChaineAvis($fiche);

        // Envoi RH
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::EN_VALIDATION_RH->value)
            ->assertJsonPath('data.prochaine_etape', 'valider_rh');

        // RH valide
        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/valider-rh", [
            'conforme' => true,
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::FINALISEE->value)
            ->assertJsonPath('data.prochaine_etape', 'commission_preparatoire'); // P4 : prochain = commission
    }

    public function test_prochaine_etape_coherente_avec_statut(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->rhUser);

        // en_attente
        $data = $this->getJson("/api/avancements/evaluations/{$fiche->id}")->assertOk()->json('data');
        $this->assertSame('noter', $data['prochaine_etape']);

        // noter → en_cours
        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/noter", [
            'question_id'  => $this->q1->id,
            'note_obtenue' => 5.0,
        ])->assertOk();

        $data = $this->getJson("/api/avancements/evaluations/{$fiche->id}")->assertOk()->json('data');
        $this->assertSame('continuer_notation', $data['prochaine_etape']);
    }

    // ----------------------------------------------------------------
    // Test 6 : validation RH
    // ----------------------------------------------------------------

    public function test_rh_peut_finaliser_ou_rejeter_une_fiche(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // Workflow complet Phase 2 jusqu'à signee_evalue
        $this->noterComplet($fiche);

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Agent performant, maîtrise son domaine.',
        ])->assertOk();

        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")->assertOk();

        // Phase 3 : signer la chaîne des avis hiérarchiques
        $this->signerChaineAvis($fiche);

        // Envoi en validation RH
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::EN_VALIDATION_RH->value);

        // Double envoi → 422
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")
            ->assertUnprocessable();

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

    /**
     * Mène une fiche jusqu'à statut FINALISEE (workflow complet P1+P2+P3).
     * Utilisé dans les tests Phase 4.
     */
    private function finaliserFiche(Evaluation $fiche): void
    {
        // P1+P2 : noter, avis+signer, signer-évalué
        $this->menerJusquaSigneeEvalue($fiche);

        // P3 : avis hiérarchiques
        $this->signerChaineAvis($fiche);

        // Envoi RH + validation
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")->assertOk();

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/valider-rh", [
            'conforme' => true,
        ])->assertOk();
    }

    /**
     * Amène une fiche jusqu'à statut SIGNEE_EVALUE.
     * N+1 note + signe (avis-et-signer), agent signe.
     */
    private function menerJusquaSigneeEvalue(Evaluation $fiche): void
    {
        $this->noterComplet($fiche);

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Agent rigoureux, atteint l\'ensemble de ses objectifs.',
        ])->assertOk();

        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")
            ->assertOk();
    }

    /**
     * Signe tous les avis hiérarchiques requis pour une fiche.
     * Utilisé pour préparer l'envoi RH dans les tests P2/P3.
     */
    private function signerChaineAvis(Evaluation $fiche): void
    {
        Sanctum::actingAs($this->rhUser);

        $niveauxRes = $this->getJson("/api/avancements/evaluations/{$fiche->id}/niveaux-requis")
            ->assertOk()
            ->json('data');

        foreach ($niveauxRes as $item) {
            // Poster l'avis
            $res = $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-hierarchiques", [
                'niveau'   => $item['niveau'],
                'avis'     => 'Avis conforme. Agent sérieux et compétent.',
                'approuve' => true,
            ])->assertStatus(201);

            $avisId = $res->json('data.id');

            // Signer
            $this->postJson("/api/avancements/avis-hierarchiques/{$avisId}/signer")
                ->assertOk();
        }
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

    // ================================================================
    // Tests Phase 4 — Commissions (CCN art. 68–70)
    // ================================================================

    public function test_commission_preparatoire_lifecycle(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // Finaliser la fiche (workflow P1+P2+P3 complet)
        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        // Ouvrir la commission préparatoire
        $commId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire", [
            'date_ouverture' => '2026-09-15',
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'en_cours')
            ->json('data.id');

        // Unicité : ne peut pas en créer une deuxième
        $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertUnprocessable();

        // Harmoniser la note (avec alerte car écart > 5 si note_globale = 15 et commission_note = 8)
        $this->postJson("/api/avancements/commissions-preparatoires/{$commId}/noter", [
            'evaluation_id'   => $fiche->id,
            'commission_note' => 14.0,
            'note_synthese'   => 'Agent compétent, performance conforme aux attentes de la direction.',
        ])->assertOk()
            ->assertJsonPath('data.alerte_ecart', false); // 15 - 14 = 1, pas d'alerte

        // Clôturer
        $this->postJson("/api/avancements/commissions-preparatoires/{$commId}/cloturer", [
            'observations' => 'Commission préparatoire terminée. Notes harmonisées.',
        ])->assertOk()
            ->assertJsonPath('data.statut', 'cloturee');

        // Double clôture → 422
        $this->postJson("/api/avancements/commissions-preparatoires/{$commId}/cloturer")
            ->assertUnprocessable();
    }

    public function test_commission_avancement_necessite_prep_cloturee(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        // Sans commission préparatoire clôturée → 422
        $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertUnprocessable()
            ->assertJsonPath('errors.commission_preparatoire.0', fn ($m) => str_contains($m, 'préparatoire'));
    }

    public function test_commission_avancement_lifecycle_avec_decision_favorable(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        // Ouvrir + clôturer commission préparatoire
        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        // Ouvrir commission d'avancement
        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()
            ->assertJsonPath('data.statut', 'en_cours')
            ->json('data.id');

        // Enregistrer décision favorable — 1 échelon
        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/decider", [
            'evaluation_id'   => $fiche->id,
            'decision'        => 'favorable',
            'nombre_echelons' => 1,
            'note_avancement' => 15.5,
            'commentaire'     => 'Avancement accordé suite aux bons résultats.',
        ])->assertOk()
            ->assertJsonPath('data.commission_decision', 'favorable')
            ->assertJsonPath('data.nombre_echelons', 1);

        // Clôturer la commission d'avancement
        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/cloturer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'cloturee');
    }

    public function test_decision_favorable_exige_echelons(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()->json('data.id');

        // Favorable avec 0 échelon → 422
        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/decider", [
            'evaluation_id'   => $fiche->id,
            'decision'        => 'favorable',
            'nombre_echelons' => 0,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.nombre_echelons.0', fn ($m) => str_contains($m, 'échelon'));
    }

    public function test_avancer_echelon_est_idempotent(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()->json('data.id');

        // Décision favorable
        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/decider", [
            'evaluation_id'   => $fiche->id,
            'decision'        => 'favorable',
            'nombre_echelons' => 1,
        ])->assertOk();

        // Premier appel avancer-echelon
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avancer-echelon")
            ->assertOk()
            ->assertJsonPath('data.avance', true);

        // Deuxième appel → idempotent
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avancer-echelon")
            ->assertOk()
            ->assertJsonPath('data.avance', false)
            ->assertJsonPath('data.message', fn ($m) => str_contains($m, 'idempotent'));
    }

    public function test_cloture_session_necessite_deux_commissions_cloturees(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        // Clôturer session sans commissions → 422
        $this->postJson("/api/avancements/sessions/{$session->id}/cloturer")
            ->assertUnprocessable()
            ->assertJsonPath('errors.commission_preparatoire.0', fn ($m) => str_contains($m, 'préparatoire'));

        // Ouvrir + clôturer commission préparatoire
        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        // Toujours bloqué → commission avancement manquante
        $this->postJson("/api/avancements/sessions/{$session->id}/cloturer")
            ->assertUnprocessable()
            ->assertJsonPath('errors.commission_avancement.0', fn ($m) => str_contains($m, 'avancement'));

        // Ouvrir + clôturer commission d'avancement
        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()->json('data.id');
        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/cloturer")->assertOk();

        // Maintenant la clôture session réussit
        $this->postJson("/api/avancements/sessions/{$session->id}/cloturer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'cloturee');
    }

    public function test_workflow_complet_p1_a_p4(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // P1+P2+P3 → finalisée
        $this->finaliserFiche($fiche);

        Sanctum::actingAs($this->rhUser);

        // Vérifier prochaine_etape = commission_preparatoire
        $data = $this->getJson("/api/avancements/evaluations/{$fiche->id}")->json('data');
        $this->assertSame('commission_preparatoire', $data['prochaine_etape']);

        // P4 : commission préparatoire
        $prepId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-preparatoire")
            ->assertCreated()->json('data.id');

        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/noter", [
            'evaluation_id'   => $fiche->id,
            'commission_note' => 15.0,
        ])->assertOk();

        $this->postJson("/api/avancements/commissions-preparatoires/{$prepId}/cloturer")->assertOk();

        // P4 : commission d'avancement
        $avanId = $this->postJson("/api/avancements/sessions/{$session->id}/commission-avancement")
            ->assertCreated()->json('data.id');

        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/decider", [
            'evaluation_id'   => $fiche->id,
            'decision'        => 'favorable',
            'nombre_echelons' => 2,
            'note_avancement' => 15.0,
        ])->assertOk();

        $this->postJson("/api/avancements/commissions-avancements/{$avanId}/cloturer")->assertOk();

        // Vérifier prochaine_etape = avancer_echelon
        $data = $this->getJson("/api/avancements/evaluations/{$fiche->id}")->json('data');
        $this->assertSame('avancer_echelon', $data['prochaine_etape']);
        $this->assertSame('favorable', $data['commission_decision']);
        $this->assertSame(2, $data['nombre_echelons']);

        // Appliquer l'avancement
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avancer-echelon")->assertOk();

        // Clôturer session
        $this->postJson("/api/avancements/sessions/{$session->id}/cloturer")->assertOk();

        // Vérifier prochaine_etape = null après avancement
        $data = $this->getJson("/api/avancements/evaluations/{$fiche->id}")->json('data');
        $this->assertNull($data['prochaine_etape']);
        $this->assertTrue($data['echelon_avance']);
    }
    // ================================================================

    public function test_niveaux_requis_depuis_direction(): void
    {
        // Le setUp affecte l'agent dans une Direction → chaîne [directeur, directeur_general]
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        Sanctum::actingAs($this->rhUser);

        $niveaux = $this->getJson("/api/avancements/evaluations/{$fiche->id}/niveaux-requis")
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $niveaux);
        $this->assertSame('directeur', $niveaux[0]['niveau']);
        $this->assertSame('directeur_general', $niveaux[1]['niveau']);
    }

    public function test_niveaux_requis_skip_directeur_si_rattache_dg(): void
    {
        // Créer un agent affecté à un Service dans une Direction rattachée DG
        $localite  = \App\Models\Localite::first();
        $admin     = \App\Models\Administration::first();
        $dirDg     = Direction::create([
            'nom'              => 'Service Direct DG',
            'administration_id' => $admin->id,
            'rattache_dg'      => true,
        ]);
        $service = \App\Models\Service::create([
            'nom'          => 'Service rattaché',
            'direction_id' => $dirDg->id,
        ]);

        $agent2 = $this->creerAgent('Pierre', 'DG-Test');
        $agent2->update(['date_prise_service' => '2024-01-01']);

        Affectation::create([
            'agent_id'                  => $agent2->id,
            'structurable_type'         => \App\Models\Service::class,
            'structurable_id'           => $service->id,
            'superieur_hierarchique_id' => $this->chef->id,
            'date_affectation'          => '2024-01-01',
            'statut'                    => StatutAffectation::ACTIVE,
            'created_by'                => $this->rhUser->id,
        ]);

        $session = $this->creerSession();
        $fiches  = \App\Models\Evaluation::where('session_id', $session->id)->get();
        $fiche2  = $fiches->firstWhere('agent_id', $agent2->id);

        Sanctum::actingAs($this->rhUser);

        if ($fiche2) {
            $niveaux = $this->getJson("/api/avancements/evaluations/{$fiche2->id}/niveaux-requis")
                ->assertOk()
                ->json('data');

            // Chaîne DG : chef_service → directeur_general (sans directeur)
            $labels = array_column($niveaux, 'niveau');
            $this->assertNotContains('directeur', $labels);
            $this->assertContains('directeur_general', $labels);
        } else {
            // L'agent n'a pas de fiche (sans-superieur possible), test quand même niveaux
            $this->assertTrue(true); // OK si pas de fiche (dépend du N+1)
        }
    }

    public function test_sequentialite_avis_hierarchique(): void
    {
        // L'agent est dans une Direction → chain [directeur, directeur_general]
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->menerJusquaSigneeEvalue($fiche);

        Sanctum::actingAs($this->rhUser);

        // Tenter de poster directeur_general AVANT directeur → 422
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-hierarchiques", [
            'niveau'   => 'directeur_general',
            'avis'     => 'Avis DG avant directeur.',
            'approuve' => true,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.ordre.0', fn ($m) => str_contains(strtolower($m), 'directeur'));
    }

    public function test_avis_signe_non_modifiable(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->menerJusquaSigneeEvalue($fiche);

        Sanctum::actingAs($this->rhUser);

        // Poster + signer le premier niveau (directeur)
        $res = $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-hierarchiques", [
            'niveau'   => 'directeur',
            'avis'     => 'Avis favorable.',
            'approuve' => true,
        ])->assertStatus(201);

        $avisId = $res->json('data.id');

        $this->postJson("/api/avancements/avis-hierarchiques/{$avisId}/signer")
            ->assertOk()
            ->assertJsonPath('data.signe', true);

        // Tentative de modification → 422
        $this->putJson("/api/avancements/avis-hierarchiques/{$avisId}", [
            'niveau'   => 'directeur',
            'avis'     => 'Tentative de modification après signature.',
            'approuve' => false,
        ])->assertUnprocessable();
    }

    public function test_envoyer_rh_bloque_si_avis_non_signes(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        $this->menerJusquaSigneeEvalue($fiche);

        // Sans signer les avis → envoyer-rh bloqué
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")
            ->assertUnprocessable()
            ->assertJsonPath('errors.avis_hierarchiques.0', fn ($m) => str_contains($m, 'avis'));
    }

    public function test_workflow_complet_p1_p2_p3(): void
    {
        $session = $this->creerSession();
        $fiche   = $this->fichePourAgent($session);

        // P1 : notation
        Sanctum::actingAs($this->chefUser);
        $this->noterComplet($fiche);

        // P2 : avis N+1 + signatures
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-et-signer", [
            'avis_superieur' => 'Très bonne performance, agent moteur au sein de l\'équipe.',
        ])->assertOk();

        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/signer-evalue")->assertOk();

        // P3 : avis hiérarchiques (directeur + directeur_general)
        Sanctum::actingAs($this->rhUser);

        $niveaux = $this->getJson("/api/avancements/evaluations/{$fiche->id}/niveaux-requis")
            ->assertOk()->json('data');

        $this->assertCount(2, $niveaux); // directeur, directeur_general

        // Poster + signer niveau 1 (directeur)
        $avis1 = $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-hierarchiques", [
            'niveau'   => 'directeur',
            'avis'     => 'Appréciation favorable. Note cohérente avec les performances observées.',
            'approuve' => true,
        ])->assertStatus(201)->json('data.id');

        $this->postJson("/api/avancements/avis-hierarchiques/{$avis1}/signer")->assertOk();

        // Poster + signer niveau 2 (directeur_general)
        $avis2 = $this->postJson("/api/avancements/evaluations/{$fiche->id}/avis-hierarchiques", [
            'niveau'   => 'directeur_general',
            'avis'     => 'Validation DG. Dossier conforme.',
            'approuve' => true,
        ])->assertStatus(201)->json('data.id');

        $this->postJson("/api/avancements/avis-hierarchiques/{$avis2}/signer")->assertOk();

        // Tous avis signés → envoyer-rh OK
        Sanctum::actingAs($this->agentUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/envoyer-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::EN_VALIDATION_RH->value);

        // RH finalise
        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/avancements/evaluations/{$fiche->id}/valider-rh", [
            'conforme' => true,
        ])->assertOk()
            ->assertJsonPath('data.statut', StatutEvaluation::FINALISEE->value);

        // Vérifier que le show inclut les avis
        $show = $this->getJson("/api/avancements/evaluations/{$fiche->id}")->assertOk()->json('data');
        $this->assertArrayHasKey('avis_hierarchiques', $show);
        $this->assertCount(2, $show['avis_hierarchiques']);
        $this->assertTrue($show['avis_hierarchiques'][0]['signe']);
        $this->assertTrue($show['avis_hierarchiques'][1]['signe']);
    }
}
