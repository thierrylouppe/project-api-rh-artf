<?php

namespace Tests\Feature;

use App\Enums\CodeTypeSanction;
use App\Enums\GraviteSanction;
use App\Enums\StatutAffectation;
use App\Enums\StatutSanction;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Direction;
use App\Models\Localite;
use App\Models\TypeSanction;
use App\Models\User;
use App\Services\SanctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DisciplineTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private User $autreRh;

    private User $dg;

    private User $chefUser;

    private User $agentUser;

    private User $lecteur;

    private Agent $agent;

    private Agent $chef;

    private TypeSanction $blame;

    private TypeSanction $miseAPied;

    private TypeSanction $licenciement;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach ([
            'consulter-discipline',
            'gerer-discipline',
            'proposer-discipline',
            'prononcer-discipline',
        ] as $name) {
            Permission::findOrCreate($name, 'api');
        }

        Role::findOrCreate('rh', 'api');
        Role::findOrCreate('directeur-general', 'api');
        Role::findOrCreate('chef-service', 'api');

        $this->agent = $this->creerAgent('Jean', 'Agent');
        $this->chef = $this->creerAgent('Marie', 'Chef');

        $this->rh = User::factory()->create();
        $this->rh->givePermissionTo(['consulter-discipline', 'gerer-discipline', 'proposer-discipline']);
        $this->rh->assignRole('rh');

        $this->autreRh = User::factory()->create();
        $this->autreRh->givePermissionTo(['consulter-discipline', 'gerer-discipline', 'proposer-discipline']);
        $this->autreRh->assignRole('rh');

        $this->dg = User::factory()->create();
        $this->dg->givePermissionTo(['consulter-discipline', 'prononcer-discipline']);
        $this->dg->assignRole('directeur-general');

        $this->chefUser = User::factory()->create(['agent_id' => $this->chef->id]);
        $this->chefUser->givePermissionTo(['proposer-discipline']);
        $this->chefUser->assignRole('chef-service');

        $this->agentUser = User::factory()->create(['agent_id' => $this->agent->id]);

        $this->lecteur = User::factory()->create();
        $this->lecteur->givePermissionTo(['consulter-discipline']);
        $this->lecteur->assignRole('directeur-general');

        $localite = Localite::create(['nom' => 'Brazzaville']);
        $admin = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $direction = Direction::create(['nom' => 'DRHL', 'administration_id' => $admin->id]);

        Affectation::create([
            'agent_id' => $this->agent->id,
            'structurable_type' => Direction::class,
            'structurable_id' => $direction->id,
            'superieur_hierarchique_id' => $this->chef->id,
            'date_affectation' => '2024-01-01',
            'statut' => StatutAffectation::ACTIVE,
            'created_by' => $this->rh->id,
        ]);

        $this->blame = TypeSanction::create([
            'nom' => 'Blâme écrit',
            'code' => CodeTypeSanction::BLAME_ECRIT,
            'gravite' => GraviteSanction::LEGER,
            'description' => 'Réprimande inscrite au dossier',
            'actif' => true,
        ]);

        $this->miseAPied = TypeSanction::create([
            'nom' => 'Mise à pied sans rémunération',
            'code' => CodeTypeSanction::MISE_A_PIED,
            'gravite' => GraviteSanction::MOYEN,
            'exige_nb_jours' => true,
            'nb_jours_min' => 1,
            'nb_jours_max' => 8,
            'actif' => true,
        ]);

        $this->licenciement = TypeSanction::create([
            'nom' => 'Licenciement',
            'code' => CodeTypeSanction::LICENCIEMENT,
            'gravite' => GraviteSanction::GRAVE,
            'actif' => true,
        ]);

        Sanctum::actingAs($this->rh);
    }

    public function test_crud_types_sanctions(): void
    {
        $created = $this->postJson('/api/discipline/types-sanctions', [
            'nom' => 'Faute prévue au règlement intérieur',
            'gravite' => GraviteSanction::MOYEN->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nom', 'Faute prévue au règlement intérieur')
            ->assertJsonPath('data.gravite', GraviteSanction::MOYEN->value)
            ->assertJsonPath('data.actif', true);

        $id = $created->json('data.id');

        $this->getJson('/api/discipline/types-sanctions')
            ->assertOk()
            ->assertJsonPath('data.0.nom', 'Blâme écrit');

        $this->putJson("/api/discipline/types-sanctions/{$id}", [
            'description' => 'Complément règlement intérieur',
        ])
            ->assertOk()
            ->assertJsonPath('data.description', 'Complément règlement intérieur');

        $this->deleteJson("/api/discipline/types-sanctions/{$this->blame->id}")
            ->assertStatus(422);

        $this->deleteJson("/api/discipline/types-sanctions/{$id}")
            ->assertOk();
    }

    public function test_circuit_n1_rh_dg_avec_piece_et_pdf(): void
    {
        Sanctum::actingAs($this->chefUser);

        $created = $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Retards répétés sans justificatif',
            'date_faits' => '2026-09-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.statut', StatutSanction::EN_ATTENTE->value)
            ->assertJsonPath('data.statut_label', 'Rapport soumis')
            ->assertJsonPath('data.prochaine_etape', 'instruire')
            ->assertJsonPath('data.agent.nom', 'Agent')
            ->assertJsonPath('data.createur.id', $this->chefUser->id);

        $id = $created->json('data.id');

        $this->getJson('/api/discipline/sanctions/mes-rapports')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->getJson('/api/discipline/sanctions')->assertForbidden();

        Sanctum::actingAs($this->rh);

        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Audition de l\'agent le 10 septembre. Faits reconnus.',
        ])->assertStatus(422);

        $this->joindrePiece($id);

        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Audition de l\'agent le 10 septembre. Faits reconnus.',
            'decision' => 'Blâme avec inscription au dossier',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutSanction::INSTRUITE->value)
            ->assertJsonPath('data.prochaine_etape', 'prononcer');

        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Le RH ne prononce pas',
        ])->assertForbidden();

        $this->getJson('/api/discipline/sanctions/a-prononcer')->assertForbidden();

        Sanctum::actingAs($this->dg);

        $this->getJson('/api/discipline/sanctions/a-prononcer')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Blâme prononcé',
            'date_decision' => '2026-09-15',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutSanction::VALIDEE->value)
            ->assertJsonPath('data.statut_label', 'Prononcée')
            ->assertJsonPath('data.prochaine_etape', null)
            ->assertJsonPath('data.date_decision', '2026-09-15')
            ->assertJsonPath('data.validateur.name', $this->dg->name);

        $this->get("/api/discipline/sanctions/{$id}/pdf-rapport")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get("/api/discipline/sanctions/{$id}/pdf-decision")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $actionsAgent = $this->agentUser->fresh()->notifications->pluck('data.action')->all();
        $this->assertEqualsCanonicalizing(['creee', 'instruite', 'validee'], $actionsAgent);
        $this->assertEqualsCanonicalizing(
            ['creee', 'validee'],
            $this->rh->fresh()->notifications->pluck('data.action')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['creee', 'validee'],
            $this->autreRh->fresh()->notifications->pluck('data.action')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['instruite'],
            $this->dg->fresh()->notifications->pluck('data.action')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['instruite', 'validee'],
            $this->chefUser->fresh()->notifications->pluck('data.action')->all()
        );
    }

    public function test_classement_uniquement_apres_instruction_par_le_dg(): void
    {
        $id = $this->ouvrirRapport();

        $this->postJson("/api/discipline/sanctions/{$id}/rejeter", [
            'commentaire' => 'Éléments à charge insuffisants',
        ])->assertForbidden();

        $this->joindrePiece($id);
        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Faits non établis',
        ])->assertOk();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/discipline/sanctions/{$id}/rejeter", [
            'commentaire' => 'Éléments à charge insuffisants',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutSanction::REJETEE->value)
            ->assertJsonPath('data.statut_label', 'Classée sans suite');

        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Trop tard',
        ])->assertStatus(422);
    }

    public function test_impossible_de_prononcer_deux_fois(): void
    {
        $id = $this->ouvrirEtInstruire();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Blâme',
        ])->assertOk();

        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Encore',
        ])->assertStatus(422);
    }

    public function test_suppression_uniquement_en_attente_et_type_ccn_protege(): void
    {
        $id = $this->ouvrirRapport();

        $this->deleteJson("/api/discipline/types-sanctions/{$this->blame->id}")
            ->assertStatus(422);

        $this->deleteJson("/api/discipline/sanctions/{$id}")->assertOk();

        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Ne doit plus exister',
        ])->assertNotFound();
    }

    public function test_historique_agent_et_avertissement(): void
    {
        $this->ouvrirRapport();

        $this->postJson('/api/discipline/avertissements', [
            'agent_id' => $this->agent->id,
            'motif' => 'Retard ponctuel signalé par le chef de service',
            'date' => '2026-06-15',
        ])
            ->assertCreated()
            ->assertJsonPath('data.agent.prenom', 'Jean')
            ->assertJsonPath('data.emetteur.name', $this->rh->name);

        $this->getJson("/api/discipline/agents/{$this->agent->id}/historique")
            ->assertOk()
            ->assertJsonPath('data.sanctions.0.motif', 'Faute à instruire')
            ->assertJsonPath('data.avertissements.0.motif', 'Retard ponctuel signalé par le chef de service');

        $this->assertGreaterThanOrEqual(1, $this->agentUser->notifications()->count());
    }

    public function test_permission_consulter_ne_permet_pas_de_creer(): void
    {
        Sanctum::actingAs($this->lecteur);

        $this->getJson('/api/discipline/types-sanctions')->assertOk();

        $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Tentative',
            'date_faits' => '2026-09-01',
        ])->assertForbidden();
    }

    public function test_n1_ne_peut_pas_proposer_hors_equipe(): void
    {
        $autre = $this->creerAgent('Luc', 'Autre');

        Sanctum::actingAs($this->chefUser);

        $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $autre->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Hors périmètre N+1',
            'date_faits' => '2026-09-01',
        ])->assertStatus(422);
    }

    public function test_mise_a_pied_exige_1_a_8_jours(): void
    {
        $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->miseAPied->id,
            'motif' => 'Absence injustifiée',
            'date_faits' => '2026-09-01',
        ])->assertStatus(422);

        $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->miseAPied->id,
            'motif' => 'Absence injustifiée',
            'date_faits' => '2026-09-01',
            'nb_jours' => 9,
        ])->assertStatus(422);

        $id = $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->miseAPied->id,
            'motif' => 'Absence injustifiée',
            'date_faits' => '2026-09-01',
            'nb_jours' => 3,
        ])->assertCreated()->json('data.id');

        $this->joindrePiece($id);
        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Faits établis',
        ])->assertOk();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Mise à pied de 3 jours',
            'date_decision' => '2026-09-20',
            'date_debut_effet' => '2026-09-21',
        ])
            ->assertOk()
            ->assertJsonPath('data.nb_jours', 3)
            ->assertJsonPath('data.date_debut_effet', '2026-09-21')
            ->assertJsonPath('data.date_fin_effet', '2026-09-23');

        $this->assertSame('actif', $this->agent->fresh()->statut);
    }

    public function test_licenciement_avec_ou_sans_indemnite(): void
    {
        $id = $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->licenciement->id,
            'motif' => 'Faute lourde',
            'date_faits' => '2026-09-01',
            'avec_indemnite' => false,
        ])
            ->assertCreated()
            ->assertJsonPath('data.avec_indemnite', false)
            ->json('data.id');

        $this->joindrePiece($id);
        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Faute lourde établie',
        ])->assertOk();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Licenciement sans indemnité',
            'avec_indemnite' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.avec_indemnite', false);

        $this->assertSame('archive', $this->agent->fresh()->statut);
        $this->assertNotNull($this->agent->fresh()->archived_at);
        $this->assertFalse((bool) $this->agentUser->fresh()->is_active);
    }

    public function test_agent_archive_refuse(): void
    {
        $archive = $this->creerAgent('Paul', 'Archive');
        $archive->update(['statut' => 'archive', 'archived_at' => now()]);

        $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $archive->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Ne doit pas passer',
            'date_faits' => '2026-09-01',
        ])->assertStatus(422);
    }

    public function test_agent_consulte_ses_dossiers_sans_voir_ceux_des_autres(): void
    {
        $id = $this->ouvrirEtInstruire();

        $autre = $this->creerAgent('Luc', 'Autre');
        $autreId = $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $autre->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Dossier d\'un autre agent',
            'date_faits' => '2026-09-02',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->agentUser);

        $this->getJson('/api/discipline/sanctions')->assertForbidden();
        $this->getJson("/api/discipline/agents/{$this->agent->id}/historique")->assertForbidden();

        $this->getJson('/api/discipline/moi/historique')
            ->assertOk()
            ->assertJsonPath('data.sanctions.0.motif', 'Faute à instruire')
            ->assertJsonMissingPath('data.sanctions.0.notes_instruction');

        $this->getJson("/api/discipline/moi/sanctions/{$id}")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutSanction::INSTRUITE->value)
            ->assertJsonMissingPath('data.notes_instruction');

        $this->getJson("/api/discipline/moi/sanctions/{$autreId}")->assertNotFound();
        $this->get("/api/discipline/moi/sanctions/{$id}/pdf-decision")->assertStatus(422);

        Sanctum::actingAs($this->rh);
        $this->getJson('/api/discipline/moi/historique')->assertForbidden();
    }

    public function test_conservation_cinq_ans_et_recidive_sur_antecedents(): void
    {
        $id = $this->ouvrirRapport();

        $this->getJson("/api/discipline/sanctions/{$id}")
            ->assertOk()
            ->assertJsonPath('data.conservee_jusqu_au', now()->addYears(5)->toDateString())
            ->assertJsonPath('data.dans_delai_conservation', true)
            ->assertJsonPath('data.recidive', false)
            ->assertJsonPath('data.antecedents_5_ans', []);

        $this->joindrePiece($id);
        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Instruction close',
        ])->assertOk();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Blâme prononcé',
        ])->assertOk();

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Nouvelle faute',
            'date_faits' => '2026-09-10',
        ])
            ->assertCreated()
            ->assertJsonPath('data.recidive', true)
            ->assertJsonPath('data.antecedents_5_ans.0.id', $id);

        $this->getJson("/api/discipline/agents/{$this->agent->id}/historique")
            ->assertOk()
            ->assertJsonPath('data.recidive', true);
    }

    public function test_mise_a_pied_suspend_puis_le_job_reactive(): void
    {
        $id = $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->miseAPied->id,
            'motif' => 'Absence injustifiée',
            'date_faits' => '2026-09-01',
            'nb_jours' => 2,
        ])->assertCreated()->json('data.id');

        $this->joindrePiece($id);
        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Faits établis',
        ])->assertOk();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/discipline/sanctions/{$id}/valider", [
            'decision' => 'Mise à pied immédiate',
            'date_debut_effet' => now()->toDateString(),
        ])->assertOk();

        $this->assertSame('suspendu', $this->agent->fresh()->statut);

        $this->app->make(SanctionService::class)
            ->appliquerEffetsMiseAPied(now()->addDays(2)->toDateString());

        $this->assertSame('actif', $this->agent->fresh()->statut);
    }

    private function ouvrirRapport(): int
    {
        return $this->postJson('/api/discipline/sanctions', [
            'agent_id' => $this->agent->id,
            'type_sanction_id' => $this->blame->id,
            'motif' => 'Faute à instruire',
            'date_faits' => '2026-09-01',
        ])->assertCreated()->json('data.id');
    }

    private function ouvrirEtInstruire(): int
    {
        $id = $this->ouvrirRapport();
        $this->joindrePiece($id);

        $this->postJson("/api/discipline/sanctions/{$id}/instruire", [
            'notes_instruction' => 'Instruction close',
        ])->assertOk();

        return $id;
    }

    private function joindrePiece(int $id): void
    {
        $this->post("/api/discipline/sanctions/{$id}/pieces", [
            'fichier' => UploadedFile::fake()->create('rapport.pdf', 20, 'application/pdf'),
        ])->assertCreated();
    }

    private function creerAgent(string $prenom, string $nom): Agent
    {
        return Agent::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => '1990-01-01',
            'genre' => 'M',
            'statut' => 'actif',
            'date_prise_service' => '2024-01-01',
        ]);
    }
}
