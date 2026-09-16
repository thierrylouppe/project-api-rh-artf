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

class PaieBulletinTest extends TestCase
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

    public function test_bulletin_enrichi_pdf_sur_ligne_generee(): void
    {
        $this->creerAgentAvecSalaire();
        $lotId = $this->creerEtGenerer(2026, 3);
        $ligneId = $this->getJson("/api/paie/lots/{$lotId}/lignes")->json('data.0.id');

        $response = $this->get("/api/paie/lots/{$lotId}/lignes/{$ligneId}/bulletin");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString(
            'bulletin-paie-',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_bulletin_indiciaire_inchange(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $salaireAgent = SalaireAgent::query()->where('agent_id', $agent->id)->firstOrFail();

        $response = $this->get("/api/salaires-agents/{$salaireAgent->id}/bulletin");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString(
            'bulletin-salaire-',
            (string) $response->headers->get('content-disposition')
        );
    }

    public function test_bulletin_enrichi_interdit_sans_permission(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->get('/api/paie/lots/1/lignes/1/bulletin')->assertForbidden();
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
