<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Grade;
use App\Models\Salaire;
use App\Models\SalaireAgent;
use App\Models\User;
use Database\Seeders\PaieElementSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaieExportTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private Classegrillesalariale $classe;

    private Salaire $salaire;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class, PaieElementSeeder::class]);
        $this->preparerGrille();

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($this->rh, ['*']);
    }

    public function test_export_refuse_avant_validation(): void
    {
        $this->creerAgentAvecSalaire();
        $id = $this->creerEtGenerer(2026, 3);

        $this->get("/api/paie/lots/{$id}/export?format=csv")->assertStatus(422);
        $this->get("/api/paie/lots/{$id}/export?format=pdf")->assertStatus(422);
    }

    public function test_export_csv_apres_validation(): void
    {
        $this->creerAgentAvecSalaire();
        $id = $this->creerValider(2026, 3);

        $response = $this->get("/api/paie/lots/{$id}/export?format=csv");

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $csv = $response->streamedContent();
        $this->assertStringContainsString('matricule;nom;prenom;hors_grille;montant_base;total_gains;total_retenues;montant_net', $csv);
        $this->assertStringContainsString('Test', $csv);
        $this->assertStringContainsString('TOTAL', $csv);
    }

    public function test_export_pdf_apres_validation(): void
    {
        $this->creerAgentAvecSalaire();
        $id = $this->creerValider(2026, 3);

        $response = $this->get("/api/paie/lots/{$id}/export?format=pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_format_invalide(): void
    {
        $this->creerAgentAvecSalaire();
        $id = $this->creerValider(2026, 3);

        $this->getJson("/api/paie/lots/{$id}/export?format=xlsx")->assertStatus(422);
        $this->getJson("/api/paie/lots/{$id}/export")->assertStatus(422);
    }

    public function test_export_interdit_sans_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->get('/api/paie/lots/1/export?format=csv')->assertForbidden();
    }

    private function creerValider(int $annee, int $mois): int
    {
        $id = $this->creerEtGenerer($annee, $mois);
        $this->postJson("/api/paie/lots/{$id}/controler")->assertOk();
        $this->postJson("/api/paie/lots/{$id}/valider")->assertOk();

        return $id;
    }

    private function creerEtGenerer(int $annee, int $mois): int
    {
        $id = $this->postJson('/api/paie/lots', ['annee' => $annee, 'mois' => $mois])
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/paie/lots/{$id}/generer")->assertOk();

        return $id;
    }

    private function creerAgentAvecSalaire(float $base = 100_000, string $prise = '2020-01-01'): Agent
    {
        $agent = Agent::create([
            'nom' => 'Test',
            'prenom' => 'Agent',
            'date_naissance' => '1990-01-01',
            'genre' => 'M',
            'statut' => 'actif',
            'date_prise_service' => $prise,
            'matricule' => 'ARTF-PAIE-001',
        ]);

        SalaireAgent::query()->create([
            'agent_id' => $agent->id,
            'salaire_id' => $this->salaire->id,
            'classegrillesalariale_id' => $this->classe->id,
            'echelon' => 1,
            'montant_base' => $base,
            'montant_net' => $base,
            'date_debut' => '2019-01-01',
            'statut' => 'actif',
        ]);

        return $agent;
    }

    private function preparerGrille(): void
    {
        $categorie = Categorie::create(['nom' => 'Classe VII', 'sigle' => 'CL-VII']);
        $grade = Grade::create(['nom' => 'Vérificateur', 'sigle' => 'VER', 'niveau' => 7]);
        $this->classe = Classegrillesalariale::create([
            'categorie_id' => $categorie->id,
            'grade_id' => $grade->id,
            'coefficient' => 105,
        ]);
        $this->salaire = Salaire::create([
            'classegrillesalariale_id' => $this->classe->id,
            'echelon' => 1,
            'indice' => 800,
            'salaire' => 100_000,
        ]);
    }
}
