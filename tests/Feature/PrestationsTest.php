<?php

namespace Tests\Feature;

use App\Enums\CodePaieElement;
use App\Enums\StatutEssai;
use App\Enums\StatutPositionConventionnelle;
use App\Enums\TypeAyantDroit;
use App\Enums\TypePiecePrestation;
use App\Enums\TypePositionConventionnelle;
use App\Enums\TypePrestation;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Contrat;
use App\Models\Grade;
use App\Models\PaieElementAffectation;
use App\Models\PositionConventionnelle;
use App\Models\Salaire;
use App\Models\SalaireAgent;
use App\Models\TypeContrat;
use App\Models\User;
use Database\Seeders\PaieElementSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrestationsTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private User $dg;

    private User $lecteur;

    private Classegrillesalariale $classe;

    private Salaire $salaire;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->seed([PermissionSeeder::class, RoleSeeder::class, PaieElementSeeder::class]);
        $this->preparerGrille();

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));

        $this->dg = User::factory()->create();
        $this->dg->assignRole(Role::findByName('directeur-general', 'api'));

        $this->lecteur = User::factory()->create();
        $this->lecteur->givePermissionTo(['consulter-affaires-sociales']);

        $this->agirEnRh();
    }

    public function test_circuit_brouillon_jusqua_accord_et_pose_paie(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');

        $created = $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::CAPITAL_DECES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'brouillon')
            ->assertJsonPath('data.prochaine_etape', 'soumettre')
            ->assertJsonPath('data.montant_calcule', 1_272_000);

        $id = $created->json('data.id');

        $this->postJson("/api/affaires-sociales/prestations/{$id}/accorder", [])
            ->assertForbidden();

        $this->postJson("/api/affaires-sociales/prestations/{$id}/soumettre")
            ->assertOk()
            ->assertJsonPath('data.statut', 'soumise');

        $this->agirEnDg();
        $this->postJson("/api/affaires-sociales/prestations/{$id}/accorder", [])
            ->assertStatus(422);

        $this->agirEnRh();
        $this->postJson("/api/affaires-sociales/prestations/{$id}/instruire", [
            'notes_instruction' => 'Barème art. 121 — palier 6 à 15 ans.',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'instruite')
            ->assertJsonPath('data.prochaine_etape', 'accorder');

        $this->postJson("/api/affaires-sociales/prestations/{$id}/accorder", [
            'paie_annee' => 2026,
            'paie_mois' => 2,
        ])
            ->assertForbidden();

        $this->agirEnDg();

        $this->postJson("/api/affaires-sociales/prestations/{$id}/accorder", [
            'paie_annee' => 2026,
            'paie_mois' => 2,
            'commentaire' => 'Accordé',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'accordee')
            ->assertJsonPath('data.montant_accorde', 1_272_000)
            ->assertJsonPath('data.paie_mois', 2);

        $affectation = PaieElementAffectation::query()->where('agent_id', $agent->id)->first();
        $this->assertNotNull($affectation);
        $this->assertEquals(1_272_000, (float) $affectation->montant);
        $this->assertSame($id, $affectation->meta['prestation_id']);
        $this->assertSame('2026-02-01', $affectation->date_debut->toDateString());

        $this->get("/api/affaires-sociales/prestations/{$id}/pdf-decision")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_art_121_paliers_et_prime_enfants(): void
    {
        $moinsUnAn = $this->creerAgentAvecSalaire(100_000, '2025-06-01');
        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $moinsUnAn->id,
            'type' => TypePrestation::CAPITAL_DECES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 500_000)
            ->assertJsonPath('data.calcul_snapshot.nb_mois_bareme', 5);

        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');
        AyantDroit::query()->create([
            'agent_id' => $agent->id,
            'type' => TypeAyantDroit::ENFANT,
            'nom' => 'Enfant',
            'prenom' => 'Un',
            'date_naissance' => '2018-01-01',
            'lien_juridique' => 'mariage',
            'qualite_age' => 'standard',
            'actif' => true,
        ]);
        AyantDroit::query()->create([
            'agent_id' => $agent->id,
            'type' => TypeAyantDroit::ENFANT,
            'nom' => 'Enfant',
            'prenom' => 'Deux',
            'date_naissance' => '2019-06-01',
            'lien_juridique' => 'mariage',
            'qualite_age' => 'standard',
            'actif' => true,
        ]);

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::PRIME_ENFANTS_DECES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 200_000)
            ->assertJsonPath('data.calcul_snapshot.nb_enfants_a_charge', 2);
    }

    public function test_capital_deces_refuse_en_periode_dessai(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2025-01-01');
        $typeContrat = TypeContrat::query()->create(['nom' => 'CDI', 'sigle' => 'CDI']);
        Contrat::query()->create([
            'agent_id' => $agent->id,
            'type_contrat_id' => $typeContrat->id,
            'date_debut' => '2025-01-01',
            'statut' => 'actif',
            'statut_essai' => StatutEssai::EN_COURS,
        ]);

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::CAPITAL_DECES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])->assertStatus(422);
    }

    public function test_frais_funeraires_plafond_et_transport(): void
    {
        $agent = $this->creerAgentAvecSalaire();

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::FRAIS_FUNERAIRES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Famille',
            'montant_demande' => 2_000_001,
        ])->assertStatus(422);

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::FRAIS_FUNERAIRES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Famille',
            'montant_demande' => 1_500_000,
            'transport_corps' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 1_500_000)
            ->assertJsonPath('data.transport_corps', true);
    }

    public function test_allocation_deces_retraite_500000_si_retraite_ou_archive(): void
    {
        $actif = $this->creerAgentAvecSalaire();
        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $actif->id,
            'type' => TypePrestation::ALLOCATION_DECES_RETRAITE->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])->assertStatus(422);

        $retraite = $this->creerAgentAvecSalaire();
        $retraite->update(['statut' => 'retraite']);

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $retraite->id,
            'type' => TypePrestation::ALLOCATION_DECES_RETRAITE->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 500_000);

        $archive = $this->creerAgentAvecSalaire();
        $archive->update(['statut' => 'archive']);

        $id = $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $archive->id,
            'type' => TypePrestation::ALLOCATION_DECES_RETRAITE->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/affaires-sociales/prestations/{$id}/soumettre")->assertOk();
        $this->postJson("/api/affaires-sociales/prestations/{$id}/instruire", [
            'notes_instruction' => 'Article 120 — forfait 500 000 F.',
        ])->assertOk();

        $this->agirEnDg();
        $this->postJson("/api/affaires-sociales/prestations/{$id}/accorder", [
            'paie_annee' => 2026,
            'paie_mois' => 3,
        ])->assertOk()->assertJsonPath('data.statut', 'accordee');

        $this->assertDatabaseHas('paie_element_affectations', [
            'agent_id' => $archive->id,
            'montant' => 500_000,
        ]);
    }

    public function test_indemnite_retraite_paliers_et_deduction_detachement(): void
    {
        $tropJeune = $this->creerAgentAvecSalaire(100_000, '2022-01-01');
        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $tropJeune->id,
            'type' => TypePrestation::INDEMNITE_RETRAITE->value,
            'date_fait' => '2026-01-01',
        ])->assertStatus(422);

        $cinqAns = $this->creerAgentAvecSalaire(100_000, '2021-01-01');
        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $cinqAns->id,
            'type' => TypePrestation::INDEMNITE_RETRAITE->value,
            'date_fait' => '2026-01-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.calcul_snapshot.nb_mois_bareme', 3)
            ->assertJsonPath('data.montant_calcule', 315_000);

        $avecDetachement = $this->creerAgentAvecSalaire(100_000, '2016-01-01');
        PositionConventionnelle::query()->create([
            'agent_id' => $avecDetachement->id,
            'type' => TypePositionConventionnelle::DETACHEMENT,
            'statut' => StatutPositionConventionnelle::CLOTUREE,
            'date_debut' => '2018-01-01',
            'date_fin' => '2020-01-01',
        ]);

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $avecDetachement->id,
            'type' => TypePrestation::INDEMNITE_RETRAITE->value,
            'date_fait' => '2026-01-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.calcul_snapshot.annees_anciennete', 8)
            ->assertJsonPath('data.calcul_snapshot.nb_mois_bareme', 6);
    }

    public function test_simulation_pieces_classer_et_permissions(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');
        $id = $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::CAPITAL_DECES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/affaires-sociales/prestations/{$id}/simulation")
            ->assertOk()
            ->assertJsonPath('data.montant', 1_272_000)
            ->assertJsonPath('data.article_ccn', '121');

        $this->post("/api/affaires-sociales/prestations/{$id}/pieces", [
            'fichier' => UploadedFile::fake()->create('acte.pdf', 120, 'application/pdf'),
            'type_piece' => TypePiecePrestation::ACTE_DECES->value,
        ])->assertCreated()->assertJsonPath('data.type_piece', 'acte_deces');

        $this->getJson("/api/affaires-sociales/agents/{$agent->id}/prestations")
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->postJson("/api/affaires-sociales/prestations/{$id}/soumettre")->assertOk();
        $this->postJson("/api/affaires-sociales/prestations/{$id}/classer", [
            'commentaire' => 'Dossier incomplet',
        ])->assertOk()->assertJsonPath('data.statut', 'classee');

        Sanctum::actingAs($this->lecteur, ['*']);
        $this->getJson('/api/affaires-sociales/prestations')->assertOk();
        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::CAPITAL_DECES->value,
            'date_fait' => '2026-01-01',
            'beneficiaire_libelle' => 'Ayants droit',
        ])->assertForbidden();
    }

    public function test_deces_exige_beneficiaire(): void
    {
        $agent = $this->creerAgentAvecSalaire();

        $this->postJson('/api/affaires-sociales/prestations', [
            'agent_id' => $agent->id,
            'type' => TypePrestation::CAPITAL_DECES->value,
            'date_fait' => '2026-01-01',
        ])->assertStatus(422);
    }

    public function test_codes_paie_prestations_actifs(): void
    {
        $codes = [
            CodePaieElement::CAPITAL_DECES,
            CodePaieElement::INDEMNITE_RETRAITE,
            CodePaieElement::PRIME_ENFANTS_DECES,
            CodePaieElement::FRAIS_FUNERAIRES,
            CodePaieElement::ALLOCATION_DECES_RETRAITE,
        ];

        foreach ($codes as $code) {
            $this->assertDatabaseHas('paie_elements', [
                'code' => $code->value,
                'actif' => true,
            ]);
        }
    }

    private function agirEnRh(): void
    {
        Sanctum::actingAs($this->rh, ['*']);
    }

    private function agirEnDg(): void
    {
        Sanctum::actingAs($this->dg, ['*']);
    }

    private function creerAgentAvecSalaire(float $base = 100_000, string $prise = '2020-01-01'): Agent
    {
        $agent = Agent::query()->create([
            'nom' => 'Test',
            'prenom' => 'Prestation',
            'date_naissance' => '1970-01-01',
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
            'date_debut' => '2010-01-01',
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
