<?php

namespace Tests\Feature;

use App\Enums\CodePaieElement;
use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\ModeCalculPaieElement;
use App\Enums\NaturePaieElement;
use App\Enums\TypeAyantDroit;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Fonction;
use App\Models\Grade;
use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
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

class PaieComplementsTest extends TestCase
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

    public function test_actions_periode_label_snapshot_et_commentaire(): void
    {
        $agent = $this->creerAgentAvecSalaire();

        $id = $this->postJson('/api/paie/lots', [
            'annee' => 2026,
            'mois' => 3,
            'commentaire' => 'Brouillon mars',
        ])
            ->assertCreated()
            ->assertJsonPath('data.periode_label', 'mars 2026')
            ->assertJsonPath('data.commentaire', 'Brouillon mars')
            ->assertJsonPath('data.actions.generer', true)
            ->assertJsonPath('data.actions.controler', false)
            ->assertJsonPath('data.actions.valider', false)
            ->assertJsonPath('data.actions.cloturer', false)
            ->assertJsonPath('data.actions.supprimer', true)
            ->assertJsonPath('data.actions.exporter', false)
            ->assertJsonPath('data.actions.modifier', true)
            ->json('data.id');

        $this->putJson("/api/paie/lots/{$id}", ['commentaire' => 'Relu RH'])
            ->assertOk()
            ->assertJsonPath('data.commentaire', 'Relu RH');

        $this->postJson("/api/paie/lots/{$id}/generer")->assertOk();

        $ligne = $this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0');
        $this->assertSame($this->classe->id, $ligne['snapshot_agent']['classe_id']);
        $this->assertSame('CL-VII', $ligne['snapshot_agent']['classe']);
        $this->assertEquals(1, $ligne['snapshot_agent']['echelon']);

        $this->postJson("/api/paie/lots/{$id}/controler")->assertOk();
        $this->postJson("/api/paie/lots/{$id}/valider")
            ->assertOk()
            ->assertJsonPath('data.actions.generer', false)
            ->assertJsonPath('data.actions.modifier', false)
            ->assertJsonPath('data.actions.exporter', true);

        $this->putJson("/api/paie/lots/{$id}", ['commentaire' => 'Trop tard'])
            ->assertStatus(422);

        $this->getJson("/api/paie/lots/{$id}/lignes?agent_id={$agent->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/paie/lots/'.$id.'/lignes?q=Inconnu')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_autos_parametres_art_58_59_et_cnss(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');
        $this->enfant($agent, 'Léo', 8);
        $this->enfant($agent, 'Aîné', 17);

        $this->parametrer(CodePaieElement::ALLOCATIONS_FAMILIALES, 5_000);
        $this->parametrer(CodePaieElement::SUPPLEMENT_FAMILIAL, 12_000);
        $this->parametrer(CodePaieElement::INDEMNITE_TRANSPORT, 15_000);
        $this->parametrer(CodePaieElement::PRIME_VESTIMENTAIRE, 30_000);
        PaieElement::query()->where('code', CodePaieElement::RETENUE_CNSS->value)
            ->update(['taux_defaut' => 4]);

        $mars = $this->creerEtGenerer(2026, 3);
        $detailsMars = collect($this->getJson("/api/paie/lots/{$mars}/lignes")->json('data.0.details'));

        $this->assertEquals(5_000, $detailsMars->firstWhere('code', 'allocations_familiales')['montant']);
        $this->assertSame('calcul_auto', $detailsMars->firstWhere('code', 'allocations_familiales')['source']);
        $this->assertEquals(12_000, $detailsMars->firstWhere('code', 'supplement_familial')['montant']);
        $this->assertEquals(15_000, $detailsMars->firstWhere('code', 'indemnite_transport')['montant']);
        $this->assertSame('calcul_auto', $detailsMars->firstWhere('code', 'indemnite_transport')['source']);
        $this->assertEquals(4_000, $detailsMars->firstWhere('code', 'retenue_cnss')['montant']);
        $this->assertNull($detailsMars->firstWhere('code', 'prime_vestimentaire'));

        $juin = $this->creerEtGenerer(2026, 6);
        $detailsJuin = collect($this->getJson("/api/paie/lots/{$juin}/lignes")->json('data.0.details'));
        $this->assertEquals(30_000, $detailsJuin->firstWhere('code', 'prime_vestimentaire')['montant']);
        $this->assertSame('calcul_auto', $detailsJuin->firstWhere('code', 'prime_vestimentaire')['source']);
    }

    public function test_affectation_prime_sur_auto_parametre(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $this->parametrer(CodePaieElement::INDEMNITE_TRANSPORT, 15_000);
        $element = PaieElement::query()->where('code', CodePaieElement::INDEMNITE_TRANSPORT->value)->firstOrFail();

        PaieElementAffectation::query()->create([
            'paie_element_id' => $element->id,
            'agent_id' => $agent->id,
            'montant' => 20_000,
            'date_debut' => '2026-01-01',
            'created_by' => $this->rh->id,
        ]);

        $id = $this->creerEtGenerer(2026, 3);
        $transport = collect($this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0.details'))
            ->firstWhere('code', 'indemnite_transport');

        $this->assertEquals(20_000, $transport['montant']);
        $this->assertSame('affectation', $transport['source']);
    }

    public function test_stagiaire_sans_autos_familiaux(): void
    {
        $stagiaire = Agent::create([
            'nom' => 'Stage',
            'prenom' => 'Iaire',
            'date_naissance' => '2000-01-01',
            'genre' => 'M',
            'statut' => 'stagiaire',
            'date_prise_service' => '2026-01-01',
        ]);
        $this->enfant($stagiaire, 'Bébé', 4);
        $this->parametrer(CodePaieElement::ALLOCATIONS_FAMILIALES, 5_000);
        $this->parametrer(CodePaieElement::INDEMNITE_TRANSPORT, 15_000);

        $transportStagiaire = PaieElement::query()
            ->where('code', CodePaieElement::PRIME_TRANSPORT_STAGIAIRE->value)
            ->firstOrFail();
        $transportStagiaire->update(['montant_defaut' => 8_000]);

        PaieElementAffectation::query()->create([
            'paie_element_id' => $transportStagiaire->id,
            'agent_id' => $stagiaire->id,
            'montant' => 8_000,
            'date_debut' => '2026-01-01',
            'created_by' => $this->rh->id,
        ]);

        $id = $this->creerEtGenerer(2026, 3);
        $details = collect($this->getJson("/api/paie/lots/{$id}/lignes")->json('data.0.details'));

        $this->assertNotNull($details->firstWhere('code', 'prime_transport_stagiaire'));
        $this->assertNull($details->firstWhere('code', 'allocations_familiales'));
        $this->assertNull($details->firstWhere('code', 'indemnite_transport'));
    }

    public function test_filtre_hors_grille_et_recherche(): void
    {
        $this->creerAgentAvecSalaire();
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

        $id = $this->creerEtGenerer(2026, 3);

        $this->getJson("/api/paie/lots/{$id}/lignes?hors_grille=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.hors_grille', true);

        $this->getJson("/api/paie/lots/{$id}/lignes?q=Hors")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.snapshot_agent.nom', 'Hors');
    }

    public function test_delete_element_maison_bloque_si_snapshot_valide(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $elementId = $this->postJson('/api/paie/elements', [
            'code' => 'retenue_avance',
            'libelle' => 'Avance',
            'nature' => NaturePaieElement::RETENUE->value,
            'periodicite' => 'ponctuel',
            'mode_calcul' => ModeCalculPaieElement::MONTANT_FIXE->value,
            'montant_defaut' => 10_000,
        ])->assertCreated()->json('data.id');

        $affectationId = $this->postJson('/api/paie/affectations', [
            'agent_id' => $agent->id,
            'paie_element_id' => $elementId,
            'montant' => 10_000,
            'date_debut' => '2026-01-01',
        ])->assertCreated()->json('data.id');

        $id = $this->creerEtGenerer(2026, 3);
        $this->deleteJson("/api/paie/affectations/{$affectationId}")->assertOk();
        $this->postJson("/api/paie/lots/{$id}/controler")->assertOk();
        $this->postJson("/api/paie/lots/{$id}/valider")->assertOk();

        $this->deleteJson("/api/paie/elements/{$elementId}")->assertStatus(422);
    }

    private function parametrer(CodePaieElement $code, float $montant): void
    {
        PaieElement::query()->where('code', $code->value)->update(['montant_defaut' => $montant]);
    }

    private function enfant(Agent $agent, string $prenom, int $age): void
    {
        AyantDroit::query()->create([
            'agent_id' => $agent->id,
            'type' => TypeAyantDroit::ENFANT,
            'nom' => $agent->nom,
            'prenom' => $prenom,
            'date_naissance' => now()->subYears($age)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::MARIAGE,
            'actif' => true,
        ]);
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
