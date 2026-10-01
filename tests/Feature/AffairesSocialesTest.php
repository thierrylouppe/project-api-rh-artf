<?php

namespace Tests\Feature;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\QualiteAgeAyantDroit;
use App\Enums\TypeAyantDroit;
use App\Enums\TypeOrganismeSocial;
use App\Enums\TypePieceAyantDroit;
use App\Models\Agent;
use App\Models\OrganismeSocial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AffairesSocialesTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private User $lecteur;

    private User $sansDroit;

    private Agent $agent;

    private OrganismeSocial $cnss;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach (['consulter-affaires-sociales', 'gerer-affaires-sociales'] as $name) {
            Permission::findOrCreate($name, 'api');
        }

        Role::findOrCreate('rh', 'api');
        Role::findOrCreate('directeur-general', 'api');

        $this->agent = $this->creerAgent('Jean', 'Mabiala', '101234567');

        $this->rh = User::factory()->create();
        $this->rh->givePermissionTo(['consulter-affaires-sociales', 'gerer-affaires-sociales']);
        $this->rh->assignRole('rh');

        $this->lecteur = User::factory()->create();
        $this->lecteur->givePermissionTo(['consulter-affaires-sociales']);

        $this->sansDroit = User::factory()->create();

        $this->cnss = OrganismeSocial::create([
            'nom' => 'Caisse Nationale de Sécurité Sociale',
            'code' => 'CNSS',
            'type' => TypeOrganismeSocial::CNSS,
            'actif' => true,
        ]);

        Sanctum::actingAs($this->rh);
    }

    public function test_crud_organismes(): void
    {
        $created = $this->postJson('/api/affaires-sociales/organismes', [
            'nom' => 'Mutuelle ARTF',
            'type' => TypeOrganismeSocial::MUTUELLE->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nom', 'Mutuelle ARTF')
            ->assertJsonPath('data.type', TypeOrganismeSocial::MUTUELLE->value)
            ->assertJsonPath('data.actif', true);

        $id = $created->json('data.id');

        $this->getJson('/api/affaires-sociales/organismes')
            ->assertOk()
            ->assertJsonPath('data.0.systeme', true);

        $this->putJson("/api/affaires-sociales/organismes/{$id}", [
            'telephone' => '06 000 00 00',
        ])->assertOk()->assertJsonPath('data.telephone', '06 000 00 00');

        $this->deleteJson("/api/affaires-sociales/organismes/{$id}")
            ->assertOk();
    }

    public function test_cnss_systeme_non_supprimable_ni_retypee(): void
    {
        $this->deleteJson("/api/affaires-sociales/organismes/{$this->cnss->id}")
            ->assertStatus(422);

        $this->putJson("/api/affaires-sociales/organismes/{$this->cnss->id}", [
            'type' => TypeOrganismeSocial::MUTUELLE->value,
        ])->assertStatus(422);

        $this->putJson("/api/affaires-sociales/organismes/{$this->cnss->id}", [
            'code' => 'AUTRE',
        ])->assertStatus(422);
    }

    public function test_affiliation_cnss_reprend_numero_agent_et_synchronise(): void
    {
        $this->getJson('/api/affaires-sociales/alertes/sans-affiliation-cnss')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->agent->id);

        $created = $this->postJson('/api/affaires-sociales/affiliations', [
            'agent_id' => $this->agent->id,
            'organisme_id' => $this->cnss->id,
            'date_debut' => '2024-01-15',
        ])
            ->assertCreated()
            ->assertJsonPath('data.numero_affiliation', '101234567')
            ->assertJsonPath('data.statut', 'active');

        $this->assertDatabaseHas('agents', [
            'id' => $this->agent->id,
            'numero_cnss' => '101234567',
        ]);

        $this->getJson('/api/affaires-sociales/alertes/sans-affiliation-cnss')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson("/api/affaires-sociales/agents/{$this->agent->id}/affiliations")
            ->assertOk()
            ->assertJsonPath('data.0.id', $created->json('data.id'));

        $this->postJson('/api/affaires-sociales/affiliations', [
            'agent_id' => $this->agent->id,
            'organisme_id' => $this->cnss->id,
            'numero_affiliation' => '999',
            'date_debut' => '2025-01-01',
        ])->assertStatus(422);
    }

    public function test_affiliation_refuse_organisme_inactif_et_agent_archive(): void
    {
        $mutuelle = OrganismeSocial::create([
            'nom' => 'Mutuelle inactive',
            'type' => TypeOrganismeSocial::MUTUELLE,
            'actif' => false,
        ]);

        $this->postJson('/api/affaires-sociales/affiliations', [
            'agent_id' => $this->agent->id,
            'organisme_id' => $mutuelle->id,
            'numero_affiliation' => 'M-1',
            'date_debut' => '2024-01-01',
        ])->assertStatus(422);

        $this->agent->update(['statut' => 'archive']);

        $this->postJson('/api/affaires-sociales/affiliations', [
            'agent_id' => $this->agent->id,
            'organisme_id' => $this->cnss->id,
            'date_debut' => '2024-01-01',
        ])->assertStatus(422);
    }

    public function test_ayants_droit_ccn_et_synchronisation_nb_enfants(): void
    {
        $conjoint = $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::CONJOINT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Claire',
            'date_naissance' => '1992-05-01',
            'lien_juridique' => LienJuridiqueAyantDroit::MARIAGE->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.a_charge', true)
            ->assertJsonPath('data.eligible_arbre_noel', false);

        $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::CONJOINT->value,
            'nom' => 'Autre',
            'prenom' => 'Conjoint',
            'date_naissance' => '1990-01-01',
            'lien_juridique' => LienJuridiqueAyantDroit::UNION_LIBRE->value,
        ])->assertStatus(422);

        $enfantACharge = $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::ENFANT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Léo',
            'date_naissance' => now()->subYears(10)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::MARIAGE->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.a_charge', true)
            ->assertJsonPath('data.eligible_arbre_noel', true)
            ->assertJsonPath('data.age_limite', 16);

        $this->assertDatabaseHas('situations_familiales', [
            'agent_id' => $this->agent->id,
            'nb_enfants' => 1,
        ]);

        $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::ENFANT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Aîné',
            'date_naissance' => now()->subYears(16)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::MARIAGE->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.a_charge', false)
            ->assertJsonPath('data.eligible_arbre_noel', true);

        $etudiant = $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::ENFANT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Nadia',
            'date_naissance' => now()->subYears(19)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::NATUREL_RECONNU->value,
            'qualite_age' => QualiteAgeAyantDroit::ETUDES->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.a_charge', true)
            ->assertJsonPath('data.age_limite', 21)
            ->assertJsonPath('data.eligible_arbre_noel', false);

        $this->assertDatabaseHas('situations_familiales', [
            'agent_id' => $this->agent->id,
            'nb_enfants' => 2,
        ]);

        $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::ENFANT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Tutelle 1',
            'date_naissance' => now()->subYears(8)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::TUTELLE->value,
        ])->assertCreated();

        $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::ENFANT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Tutelle 2',
            'date_naissance' => now()->subYears(7)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::TUTELLE->value,
        ])->assertCreated();

        $this->postJson('/api/affaires-sociales/ayants-droit', [
            'agent_id' => $this->agent->id,
            'type' => TypeAyantDroit::ENFANT->value,
            'nom' => 'Mabiala',
            'prenom' => 'Tutelle 3',
            'date_naissance' => now()->subYears(6)->toDateString(),
            'lien_juridique' => LienJuridiqueAyantDroit::TUTELLE->value,
        ])->assertStatus(422);

        $this->postJson("/api/affaires-sociales/ayants-droit/{$enfantACharge->json('data.id')}/pieces", [
            'type_piece' => TypePieceAyantDroit::ACTE_NAISSANCE->value,
            'fichier' => UploadedFile::fake()->create('acte.pdf', 20, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('data.type_piece', TypePieceAyantDroit::ACTE_NAISSANCE->value);

        $this->getJson("/api/affaires-sociales/agents/{$this->agent->id}/dossier-social")
            ->assertOk()
            ->assertJsonPath('data.synthese.a_conjoint_a_charge', true)
            ->assertJsonPath('data.synthese.nb_enfants_a_charge', 4)
            ->assertJsonPath('data.synthese.nb_enfants_arbre_noel', 3)
            ->assertJsonPath('data.synthese.prime_arbre_noel_forfaitaire', false)
            ->assertJsonPath('data.synthese.nb_enfants_tutelle', 2);

        $this->deleteJson("/api/affaires-sociales/ayants-droit/{$etudiant->json('data.id')}")
            ->assertOk();

        $this->assertDatabaseHas('situations_familiales', [
            'agent_id' => $this->agent->id,
            'nb_enfants' => 3,
        ]);

        $this->getJson("/api/affaires-sociales/ayants-droit/{$conjoint->json('data.id')}")
            ->assertOk()
            ->assertJsonPath('data.type', 'conjoint');
    }

    public function test_permissions_lecture_et_ecriture(): void
    {
        Sanctum::actingAs($this->lecteur);

        $this->getJson('/api/affaires-sociales/organismes')->assertOk();
        $this->postJson('/api/affaires-sociales/organismes', [
            'nom' => 'Interdit',
            'type' => TypeOrganismeSocial::AUTRE->value,
        ])->assertForbidden();

        Sanctum::actingAs($this->sansDroit);

        $this->getJson('/api/affaires-sociales/organismes')->assertForbidden();
    }

    public function test_organisme_utilise_non_supprimable(): void
    {
        $this->postJson('/api/affaires-sociales/affiliations', [
            'agent_id' => $this->agent->id,
            'organisme_id' => $this->cnss->id,
            'date_debut' => '2024-01-01',
        ])->assertCreated();

        $mutuelle = OrganismeSocial::create([
            'nom' => 'Mutuelle utilisée',
            'type' => TypeOrganismeSocial::MUTUELLE,
            'actif' => true,
        ]);

        $this->postJson('/api/affaires-sociales/affiliations', [
            'agent_id' => $this->agent->id,
            'organisme_id' => $mutuelle->id,
            'numero_affiliation' => 'MUT-1',
            'date_debut' => '2024-01-01',
        ])->assertCreated();

        $this->deleteJson("/api/affaires-sociales/organismes/{$mutuelle->id}")
            ->assertStatus(422);
    }

    private function creerAgent(string $prenom, string $nom, ?string $numeroCnss = null): Agent
    {
        return Agent::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'date_naissance' => '1990-01-01',
            'genre' => 'M',
            'statut' => 'actif',
            'date_prise_service' => '2024-01-01',
            'numero_cnss' => $numeroCnss,
        ]);
    }
}
