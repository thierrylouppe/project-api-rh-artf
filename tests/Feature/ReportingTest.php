<?php

namespace Tests\Feature;

use App\Enums\StatutDemandeConge;
use App\Enums\StatutEvaluation;
use App\Enums\StatutPaieLot;
use App\Enums\StatutSessionEvaluation;
use App\Models\Agent;
use App\Models\ContactUrgence;
use App\Models\DemandeConge;
use App\Models\Evaluation;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\InformationsPersonnelle;
use App\Models\InformationsProfessionnelle;
use App\Models\PaieLot;
use App\Models\SessionEvaluation;
use App\Models\TypeConge;
use App\Models\TypeIntegration;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
    }

    private function agirEnRh(): void
    {
        Sanctum::actingAs($this->rh, ['*']);
    }

    public function test_dashboard_refuse_sans_authentification(): void
    {
        $this->getJson('/api/reporting/dashboard')->assertUnauthorized();
    }

    public function test_dashboard_interdit_sans_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson('/api/reporting/dashboard')->assertForbidden();
    }

    public function test_dashboard_autorise_rh_et_dg(): void
    {
        $this->agirEnRh();
        $this->creerAgent('actif');
        $this->creerAgent('stagiaire', 'stagiaire');
        $this->creerAgent('suspendu', 'suspendu');
        $this->creerAgent('retraite', 'retraite');

        $this->getJson('/api/reporting/dashboard')
            ->assertOk()
            ->assertJsonPath('data.effectif.total', 3)
            ->assertJsonPath('data.effectif.stagiaires', 1)
            ->assertJsonPath('data.effectif.suspendus', 1)
            ->assertJsonPath('data.masse_salariale', null);

        $dg = User::factory()->create();
        $dg->assignRole(Role::findByName('directeur-general', 'api'));
        Sanctum::actingAs($dg, ['*']);

        $this->getJson('/api/reporting/dashboard')
            ->assertOk()
            ->assertJsonPath('data.effectif.total', 3);
    }

    public function test_dashboard_entrees_sorties_et_masse(): void
    {
        $this->agirEnRh();
        $this->creerAgent('entrant', 'actif', [
            'date_prise_service' => now()->startOfYear()->addDays(10)->toDateString(),
        ]);
        Agent::create([
            'nom' => 'Sorti',
            'prenom' => 'Agent',
            'date_naissance' => '1980-01-01',
            'genre' => 'F',
            'statut' => 'archive',
            'archived_at' => now()->startOfYear()->addMonths(2),
        ]);

        PaieLot::create([
            'annee' => 2026,
            'mois' => 3,
            'statut' => StatutPaieLot::CLOTURE,
            'total_gains' => 1000,
            'total_retenues' => 100,
            'total_net' => 900,
            'nb_lignes' => 2,
        ]);

        $this->getJson('/api/reporting/dashboard?annee='.now()->year)
            ->assertOk()
            ->assertJsonPath('data.mouvements.entrees', 1)
            ->assertJsonPath('data.mouvements.sorties', 1)
            ->assertJsonPath('data.masse_salariale.total_net', 900)
            ->assertJsonPath('data.masse_salariale.mois', 3);
    }

    public function test_repartitions_axes_et_validation(): void
    {
        $this->agirEnRh();
        $type = TypeIntegration::create(['nom' => 'Recrutement externe']);
        $grade = Grade::create(['nom' => 'Attaché', 'sigle' => 'ATT']);
        $fonction = Fonction::create(['nom' => 'Chef de Bureau', 'sigle' => 'CB']);

        $this->creerAgent('Marie', 'actif', [
            'genre' => 'F',
            'date_naissance' => now()->subYears(30)->toDateString(),
            'type_integration_id' => $type->id,
            'grade_id' => $grade->id,
            'fonction_id' => $fonction->id,
        ]);

        $this->getJson('/api/reporting/repartitions')->assertStatus(422);

        $this->getJson('/api/reporting/repartitions?axe=inconnu')->assertStatus(422);

        $this->getJson('/api/reporting/repartitions?axe=genre')
            ->assertOk()
            ->assertJsonPath('data.axe', 'genre');

        $genres = collect($this->getJson('/api/reporting/repartitions?axe=genre')->json('data.items'));
        $this->assertSame(1, (int) $genres->firstWhere('cle', 'F')['total']);

        $this->getJson('/api/reporting/repartitions?axe=type_integration')
            ->assertOk()
            ->assertJsonFragment(['libelle' => 'Recrutement externe']);

        $this->getJson('/api/reporting/repartitions?axe=fonction')
            ->assertOk()
            ->assertJsonFragment(['libelle' => 'Chef de Bureau']);

        $ages = collect($this->getJson('/api/reporting/repartitions?axe=age')->json('data.items'));
        $this->assertSame(1, (int) $ages->firstWhere('cle', '25_34')['total']);
    }

    public function test_effectifs_pagines(): void
    {
        $this->agirEnRh();
        $this->creerAgent('actif');
        $this->creerAgent('stagiaire', 'stagiaire');

        $this->getJson('/api/reporting/effectifs')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.statut', 'actif');
    }

    public function test_stats_conges(): void
    {
        $this->agirEnRh();
        $agent = $this->creerAgent('conge');
        $type = TypeConge::create([
            'nom' => 'Annuel',
            'necessite_n1' => true,
            'necessite_rh' => true,
            'necessite_dg' => false,
            'debite_solde' => true,
        ]);

        DemandeConge::create([
            'agent_id' => $agent->id,
            'type_conge_id' => $type->id,
            'date_debut' => now()->startOfYear()->addDays(5)->toDateString(),
            'date_fin' => now()->startOfYear()->addDays(10)->toDateString(),
            'nb_jours' => 5,
            'statut' => StatutDemandeConge::VALIDEE_RH,
        ]);

        $this->getJson('/api/reporting/stats/conges?annee='.now()->year)
            ->assertOk()
            ->assertJsonPath('data.demandes.total', 1)
            ->assertJsonPath('data.demandes.jours_accordes', 5)
            ->assertJsonPath('data.demandes.jours_poses', 5);
    }

    public function test_stats_evaluations_annee_et_session_courante(): void
    {
        $this->agirEnRh();
        $agent = $this->creerAgent('evalue');
        $session = SessionEvaluation::create([
            'debut_session' => now()->startOfYear()->toDateString(),
            'fin_session' => now()->endOfYear()->toDateString(),
            'statut' => StatutSessionEvaluation::OUVERTE,
            'description' => 'Session test',
        ]);

        Evaluation::create([
            'session_id' => $session->id,
            'agent_id' => $agent->id,
            'superieur_id' => $agent->id,
            'statut' => StatutEvaluation::FINALISEE,
            'note_globale' => 16,
            'mention' => 'Excellent',
        ]);

        $this->getJson('/api/reporting/stats/evaluations?annee='.now()->year)
            ->assertOk()
            ->assertJsonPath('data.annee.fiches.total', 1)
            ->assertJsonPath('data.session_courante.id', $session->id)
            ->assertJsonPath('data.session_courante.fiches.moyenne', 16);
    }

    public function test_alertes_dossier_incomplet_et_sans_n1(): void
    {
        $this->agirEnRh();
        $incomplet = $this->creerAgent('incomplet');
        $complet = $this->creerAgent('complet');

        InformationsPersonnelle::create(['agent_id' => $complet->id, 'ville' => 'Brazzaville']);
        InformationsProfessionnelle::create(['agent_id' => $complet->id, 'niveau_etude' => 'Master']);
        ContactUrgence::create([
            'agent_id' => $complet->id,
            'nom' => 'Tuteur',
            'prenom' => 'Paul',
            'telephone' => '060000000',
            'relation' => 'frère',
        ]);

        $response = $this->getJson('/api/reporting/alertes')->assertOk();
        $alertes = collect($response->json('data'))->keyBy('code');

        $this->assertSame(2, $alertes['sans_n1']['total']);
        $this->assertGreaterThanOrEqual(1, $alertes['dossier_incomplet']['total']);
        $this->assertTrue(collect($alertes['dossier_incomplet']['items'])->contains(fn ($item) => $item['id'] === $incomplet->id));
        $this->assertArrayHasKey('contrat_echeance_30', $alertes);
        $this->assertArrayHasKey('contrat_echeance_60', $alertes);
        $this->assertArrayHasKey('poste_vacant', $alertes);
        $this->assertArrayHasKey('sans_affiliation_cnss', $alertes);
    }

    public function test_export_csv_pdf_et_validation(): void
    {
        $this->agirEnRh();
        $this->creerAgent('export');

        $csv = $this->get('/api/reporting/exports/effectifs?format=csv');
        $csv->assertOk();
        $this->assertStringContainsString('text/csv', (string) $csv->headers->get('content-type'));
        $this->assertStringContainsString('matricule;nom;prenom', $csv->streamedContent());

        $pdf = $this->get('/api/reporting/exports/effectifs?format=pdf');
        $pdf->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $this->getJson('/api/reporting/exports/effectifs?format=xlsx')->assertStatus(422);
        $this->getJson('/api/reporting/exports/inconnu?format=csv')->assertStatus(422);
        $this->getJson('/api/reporting/exports/evaluations?format=csv&portee=session')->assertStatus(422);
    }

    private function creerAgent(string $prenom, string $statut = 'actif', array $extra = []): Agent
    {
        return Agent::create(array_merge([
            'nom' => 'Test',
            'prenom' => $prenom,
            'date_naissance' => '1990-01-01',
            'genre' => 'M',
            'statut' => $statut,
            'matricule' => 'M'.uniqid(),
        ], $extra));
    }
}
