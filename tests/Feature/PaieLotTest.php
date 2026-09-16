<?php

namespace Tests\Feature;

use App\Enums\CodePaieElement;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
use App\Models\PaieLot;
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

class PaieLotTest extends TestCase
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

    public function test_periode_unique(): void
    {
        $this->postJson('/api/paie/lots', ['annee' => 2026, 'mois' => 3])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'brouillon')
            ->assertJsonPath('data.periode', '2026-03');

        $this->postJson('/api/paie/lots', ['annee' => 2026, 'mois' => 3])
            ->assertStatus(422);
    }

    public function test_circuit_statuts_et_regen(): void
    {
        $this->creerAgentAvecSalaire();

        $id = $this->postJson('/api/paie/lots', ['annee' => 2026, 'mois' => 3])
            ->assertCreated()
            ->json('data.id');

        $this->postJson("/api/paie/lots/{$id}/generer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'genere')
            ->assertJsonPath('message', 'Lot généré')
            ->assertJsonPath('data.nb_lignes', 1);

        $this->postJson("/api/paie/lots/{$id}/generer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'genere')
            ->assertJsonPath('message', 'Lot recalculé');

        $this->postJson("/api/paie/lots/{$id}/controler")
            ->assertOk()
            ->assertJsonPath('data.statut', 'controle');

        $this->postJson("/api/paie/lots/{$id}/generer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'genere');

        $this->postJson("/api/paie/lots/{$id}/controler")->assertOk();

        $this->postJson("/api/paie/lots/{$id}/valider")
            ->assertOk()
            ->assertJsonPath('data.statut', 'valide');

        $this->postJson("/api/paie/lots/{$id}/generer")->assertStatus(422);

        $this->postJson("/api/paie/lots/{$id}/cloturer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'cloture');

        $this->postJson("/api/paie/lots/{$id}/generer")->assertStatus(422);
        $this->deleteJson("/api/paie/lots/{$id}")->assertStatus(422);
    }

    public function test_generation_base_et_anciennete(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2024-03-31');

        $id = $this->creerLot(2026, 3);
        $this->postJson("/api/paie/lots/{$id}/generer")->assertOk();

        $lignes = $this->getJson("/api/paie/lots/{$id}/lignes")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->json('data');

        $this->assertEquals(100_000, $lignes[0]['montant_base']);
        $codes = collect($lignes[0]['details'])->pluck('montant', 'code');
        $this->assertEquals(100_000, $codes['salaire_base']);
        $this->assertEquals(2_000, $codes['prime_anciennete']);
        $this->assertEquals(102_000, $lignes[0]['montant_net']);

        $ligneId = $lignes[0]['id'];
        $this->getJson("/api/paie/lots/{$id}/lignes/{$ligneId}")
            ->assertOk()
            ->assertJsonPath('data.agent.id', $agent->id)
            ->assertJsonPath('data.snapshot_agent.nom', 'Test');
    }

    public function test_affectation_mensuelle_dans_le_lot(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $element = PaieElement::query()->where('code', CodePaieElement::INDEMNITE_TRANSPORT->value)->firstOrFail();

        PaieElementAffectation::query()->create([
            'paie_element_id' => $element->id,
            'agent_id' => $agent->id,
            'montant' => 20_000,
            'date_debut' => '2026-01-01',
            'created_by' => $this->rh->id,
        ]);

        $id = $this->creerLot(2026, 3);
        $this->postJson("/api/paie/lots/{$id}/generer")->assertOk();

        $details = collect($this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0.details'));
        $this->assertEquals(20_000, $details->firstWhere('code', 'indemnite_transport')['montant']);
        $this->assertSame('affectation', $details->firstWhere('code', 'indemnite_transport')['source']);
    }

    public function test_hors_grille_sans_fonctionnel_bloque_la_validation(): void
    {
        $dg = Fonction::create(['nom' => 'Directeur Général', 'sigle' => 'DG']);
        Agent::create([
            'nom' => 'Hors',
            'prenom' => 'Grille',
            'date_naissance' => '1975-01-01',
            'genre' => 'M',
            'statut' => 'actif',
            'date_prise_service' => '2015-01-01',
            'fonction_id' => $dg->id,
        ]);

        $id = $this->creerLot(2026, 3);
        $this->postJson("/api/paie/lots/{$id}/generer")
            ->assertOk()
            ->assertJsonPath('data.nb_lignes', 1);

        $controle = $this->postJson("/api/paie/lots/{$id}/controler")
            ->assertOk()
            ->json('data');

        $this->assertSame('controle', $controle['statut']);
        $this->assertGreaterThan(0, $controle['nb_anomalies_bloquantes']);
        $this->assertContains('hors_grille_sans_fonctionnel', collect($controle['anomalies'])->pluck('code')->all());

        $this->postJson("/api/paie/lots/{$id}/valider")->assertStatus(422);
    }

    public function test_suppression_brouillon_et_interdit_sans_permission(): void
    {
        $id = $this->creerLot(2026, 4);
        $this->deleteJson("/api/paie/lots/{$id}")->assertOk();
        $this->assertNull(PaieLot::query()->find($id));

        Sanctum::actingAs(User::factory()->create(), ['*']);
        $this->getJson('/api/paie/lots')->assertForbidden();
    }

    public function test_filtre_liste(): void
    {
        $this->creerLot(2026, 1);
        $this->creerLot(2026, 2);

        $this->getJson('/api/paie/lots?mois=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mois', 1);
    }

    private function creerLot(int $annee, int $mois): int
    {
        return $this->postJson('/api/paie/lots', ['annee' => $annee, 'mois' => $mois])
            ->assertCreated()
            ->json('data.id');
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
