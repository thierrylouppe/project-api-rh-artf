<?php

namespace Tests\Feature;

use App\Enums\CodeTypeSanction;
use App\Enums\GraviteSanction;
use App\Enums\StatutSanction;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Grade;
use App\Models\PaieElement;
use App\Models\Salaire;
use App\Models\SalaireAgent;
use App\Models\Sanction;
use App\Models\TypeSanction;
use App\Models\User;
use Database\Seeders\PaieElementSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaieFinAnneeTest extends TestCase
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

    public function test_decembre_prime_totale_si_present_et_anciennete(): void
    {
        $this->creerAgentAvecSalaire(100_000, '2020-01-01');

        $id = $this->creerEtGenerer(2026, 12);
        $details = collect($this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0.details'));

        $this->assertEquals(6_000, $details->firstWhere('code', 'prime_anciennete')['montant']);
        $this->assertEquals(106_000, $details->firstWhere('code', 'prime_fin_annee')['montant']);
        $this->assertSame('calcul_auto', $details->firstWhere('code', 'prime_fin_annee')['source']);
    }

    public function test_licenciement_faute_lourde_sans_prime(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');
        $type = TypeSanction::create([
            'nom' => 'Licenciement',
            'code' => CodeTypeSanction::LICENCIEMENT,
            'gravite' => GraviteSanction::GRAVE,
            'actif' => true,
        ]);
        Sanction::query()->create([
            'agent_id' => $agent->id,
            'type_sanction_id' => $type->id,
            'motif' => 'Faute lourde',
            'date_faits' => '2026-04-01',
            'date_decision' => '2026-05-01',
            'avec_indemnite' => false,
            'statut' => StatutSanction::VALIDEE,
            'created_by' => $this->rh->id,
        ]);

        $id = $this->creerEtGenerer(2026, 12);
        $codes = collect($this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0.details'))->pluck('code');

        $this->assertContains('prime_anciennete', $codes);
        $this->assertNotContains('prime_fin_annee', $codes);
    }

    public function test_controle_liste_les_departs_de_lannee(): void
    {
        $this->creerAgentAvecSalaire();
        Agent::create([
            'nom' => 'Parti',
            'prenom' => 'Detache',
            'date_naissance' => '1985-01-01',
            'genre' => 'M',
            'statut' => 'detachement',
            'date_prise_service' => '2018-01-01',
        ]);

        $id = $this->creerEtGenerer(2026, 12);
        $controle = $this->postJson("/api/paie/lots/{$id}/controler")->assertOk()->json('data');

        $this->assertContains('fin_annee_depart', collect($controle['anomalies'])->pluck('code')->all());
    }

    public function test_arbre_de_noel_ignore_sans_montant_parametre(): void
    {
        $this->creerAgentAvecSalaire();
        $arbre = PaieElement::query()->where('code', 'allocation_arbre_noel')->firstOrFail();
        $this->assertNull($arbre->montant_defaut);

        $id = $this->creerEtGenerer(2026, 12);
        $codes = collect($this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0.details'))->pluck('code');
        $this->assertNotContains('allocation_arbre_noel', $codes);

        $controle = $this->postJson("/api/paie/lots/{$id}/controler")->assertOk()->json('data');
        $this->assertContains('prime_sans_parametre', collect($controle['anomalies'])->pluck('code')->all());
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
