<?php

namespace Tests\Feature;

use App\Enums\CodePaieElement;
use App\Enums\NatureArretSante;
use App\Enums\TypeAyantDroit;
use App\Enums\TypePieceSante;
use App\Enums\TypePriseEnCharge;
use App\Enums\TypeStructureSanitaire;
use App\Enums\TypeVisiteMedicale;
use App\Models\Agent;
use App\Models\AyantDroit;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Grade;
use App\Models\PaieElementAffectation;
use App\Models\Salaire;
use App\Models\SalaireAgent;
use App\Models\StructureSanitaire;
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

class SanteTest extends TestCase
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

    public function test_structure_nom_unique_et_suppression_refusee_si_utilisee(): void
    {
        $this->postJson('/api/affaires-sociales/structures-sanitaires', [
            'nom' => 'Clinique du Port',
            'type' => TypeStructureSanitaire::FORMATION_SANITAIRE->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nom', 'Clinique du Port');

        $this->postJson('/api/affaires-sociales/structures-sanitaires', [
            'nom' => 'Clinique du Port',
            'type' => TypeStructureSanitaire::MEDECIN->value,
        ])->assertStatus(422);

        $structure = $this->creerStructure('Dr. Kaya', TypeStructureSanitaire::MEDECIN);
        $agent = $this->creerAgentAvecSalaire();

        $this->postJson('/api/affaires-sociales/visites-medicales', [
            'agent_id' => $agent->id,
            'type' => TypeVisiteMedicale::EMBAUCHE->value,
            'date_visite' => '2026-01-15',
            'structure_sanitaire_id' => $structure->id,
        ])->assertCreated();

        $this->deleteJson("/api/affaires-sociales/structures-sanitaires/{$structure->id}")
            ->assertStatus(422);
    }

    public function test_alerte_visite_annuelle_manquante(): void
    {
        $sansVisite = $this->creerAgentAvecSalaire();
        $avecAnnuelle = $this->creerAgentAvecSalaire();
        $structure = $this->creerStructure('Médecin du travail', TypeStructureSanitaire::MEDECIN);

        $this->postJson('/api/affaires-sociales/visites-medicales', [
            'agent_id' => $avecAnnuelle->id,
            'type' => TypeVisiteMedicale::ANNUELLE->value,
            'date_visite' => now()->year.'-03-01',
            'structure_sanitaire_id' => $structure->id,
        ])->assertCreated();

        $this->postJson('/api/affaires-sociales/visites-medicales', [
            'agent_id' => $sansVisite->id,
            'type' => TypeVisiteMedicale::EMBAUCHE->value,
            'date_visite' => now()->year.'-02-01',
            'structure_sanitaire_id' => $structure->id,
        ])->assertCreated();

        $ids = collect($this->getJson('/api/affaires-sociales/alertes/visites-annuelles-manquantes')
            ->assertOk()
            ->json('data'))
            ->pluck('id');

        $this->assertTrue($ids->contains($sansVisite->id));
        $this->assertFalse($ids->contains($avecAnnuelle->id));
    }

    public function test_pharma_80_pourcent_et_circuit_accord_paie(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $pharmacie = $this->creerStructure('Pharmacie Centrale', TypeStructureSanitaire::PHARMACIE);

        $created = $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::PHARMACEUTIQUE->value,
            'date_soins' => '2026-03-01',
            'structure_sanitaire_id' => $pharmacie->id,
            'montant_facture' => 10_000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.statut', 'brouillon')
            ->assertJsonPath('data.montant_calcule', 8_000)
            ->assertJsonPath('data.calcul_snapshot.taux_employeur', 0.8);

        $id = $created->json('data.id');

        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/accorder", [])
            ->assertForbidden();

        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/soumettre")->assertOk();
        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/instruire", [
            'notes_instruction' => 'Ordonnance et facture conformes (art. 124).',
        ])->assertOk()->assertJsonPath('data.prochaine_etape', 'accorder');

        $this->agirEnDg();
        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/accorder", [
            'paie_annee' => 2026,
            'paie_mois' => 4,
            'commentaire' => 'Accordé',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'accordee')
            ->assertJsonPath('data.montant_accorde', 8_000);

        $affectation = PaieElementAffectation::query()->where('agent_id', $agent->id)->first();
        $this->assertNotNull($affectation);
        $this->assertEquals(8_000, (float) $affectation->montant);
        $this->assertSame($id, $affectation->meta['prise_en_charge_id']);
        $this->assertSame(CodePaieElement::REMBOURSEMENT_SANTE->value, $affectation->element->code);

        $this->get("/api/affaires-sociales/prises-en-charge/{$id}/pdf-decision")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_verres_reserves_au_salarie_et_structure_doit_matcher(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $opticien = $this->creerStructure('Optique Vision', TypeStructureSanitaire::OPTICIEN);
        $pharmacie = $this->creerStructure('Pharma mismatch', TypeStructureSanitaire::PHARMACIE);
        $enfant = $this->creerEnfant($agent);

        $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::VERRES_CORRECTEURS->value,
            'date_soins' => '2026-03-01',
            'structure_sanitaire_id' => $opticien->id,
            'ayant_droit_id' => $enfant->id,
            'montant_facture' => 50_000,
        ])->assertStatus(422);

        $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::HONORAIRES_SOINS->value,
            'date_soins' => '2026-03-01',
            'structure_sanitaire_id' => $pharmacie->id,
            'montant_facture' => 20_000,
        ])->assertStatus(422);

        $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::VERRES_CORRECTEURS->value,
            'date_soins' => '2026-03-01',
            'structure_sanitaire_id' => $opticien->id,
            'montant_facture' => 50_000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 50_000);
    }

    public function test_evacuation_plafond_six_mois_sauf_at_mp(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $clinique = $this->creerStructure('CHU Brazzaville', TypeStructureSanitaire::FORMATION_SANITAIRE);

        $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::EVACUATION_SANITAIRE->value,
            'date_soins' => '2026-01-01',
            'structure_sanitaire_id' => $clinique->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-08-01',
            'lieu' => 'Paris',
        ])->assertStatus(422);

        $id = $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::EVACUATION_SANITAIRE->value,
            'date_soins' => '2026-01-01',
            'structure_sanitaire_id' => $clinique->id,
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-08-01',
            'lieu' => 'Paris',
            'at_mp' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 0)
            ->json('data.id');

        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/soumettre")->assertOk();
        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/instruire", [
            'notes_instruction' => 'Évacuation AT, durée supérieure à 6 mois autorisée.',
        ])->assertOk();

        $this->agirEnDg();
        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/accorder", [])
            ->assertOk()
            ->assertJsonPath('data.statut', 'accordee')
            ->assertJsonPath('data.paie_element_affectation_id', null);

        $this->assertDatabaseMissing('paie_element_affectations', ['agent_id' => $agent->id]);
    }

    public function test_art_132_anciennete_et_majoration_133(): void
    {
        $tropJeune = $this->creerAgentAvecSalaire(100_000, '2025-06-01');
        $medecin = $this->creerStructure('Dr. Maladie', TypeStructureSanitaire::MEDECIN);

        $this->postJson('/api/affaires-sociales/arrets', [
            'agent_id' => $tropJeune->id,
            'nature' => NatureArretSante::MALADIE->value,
            'date_fait' => '2026-01-01',
            'date_debut' => '2026-01-02',
            'structure_sanitaire_id' => $medecin->id,
        ])->assertStatus(422);

        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');
        $this->creerEnfant($agent, 'Un');
        $this->creerEnfant($agent, 'Deux');

        $this->postJson('/api/affaires-sociales/arrets', [
            'agent_id' => $agent->id,
            'nature' => NatureArretSante::MALADIE->value,
            'date_fait' => '2026-01-01',
            'date_debut' => '2026-03-15',
            'structure_sanitaire_id' => $medecin->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nb_mois', 11)
            ->assertJsonPath('data.nb_mois_majoration', 4)
            ->assertJsonPath('data.montant_mensuel', 106_000)
            ->assertJsonPath('data.calcul_snapshot.nb_mois_bareme', 7);
    }

    public function test_art_135_deux_affectations_et_alerte_72h(): void
    {
        $agent = $this->creerAgentAvecSalaire(100_000, '2020-01-01');
        $medecin = $this->creerStructure('Dr. Accident', TypeStructureSanitaire::MEDECIN);

        $id = $this->postJson('/api/affaires-sociales/arrets', [
            'agent_id' => $agent->id,
            'nature' => NatureArretSante::ACCIDENT_NON_PROFESSIONNEL->value,
            'date_fait' => '2026-03-01',
            'date_notification' => '2026-03-05',
            'date_debut' => '2026-03-15',
            'structure_sanitaire_id' => $medecin->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.alerte_72h', true)
            ->assertJsonPath('data.nb_mois', 12)
            ->assertJsonPath('data.montant_mensuel', 106_000)
            ->assertJsonPath('data.montant_mensuel_demi', 56_000)
            ->json('data.id');

        $this->postJson("/api/affaires-sociales/arrets/{$id}/soumettre")->assertOk();
        $this->postJson("/api/affaires-sociales/arrets/{$id}/instruire", [
            'notes_instruction' => 'Certificat manquant volontairement.',
        ])->assertStatus(422);

        $this->post("/api/affaires-sociales/arrets/{$id}/pieces", [
            'fichier' => UploadedFile::fake()->create('certificat.pdf', 80, 'application/pdf'),
            'type_piece' => TypePieceSante::CERTIFICAT->value,
        ])->assertCreated();

        $this->postJson("/api/affaires-sociales/arrets/{$id}/instruire", [
            'notes_instruction' => 'Art. 135 — 6 mois plein puis 6 mois demi. Notification tardive signalée.',
        ])->assertOk();

        $this->agirEnDg();
        $this->postJson("/api/affaires-sociales/arrets/{$id}/accorder", [
            'commentaire' => 'Accordé',
        ])->assertOk()->assertJsonPath('data.statut', 'accordee');

        $affectations = PaieElementAffectation::query()
            ->where('agent_id', $agent->id)
            ->orderBy('date_debut')
            ->get();

        $this->assertCount(2, $affectations);
        $this->assertEquals(106_000, (float) $affectations[0]->montant);
        $this->assertEquals(56_000, (float) $affectations[1]->montant);
        $this->assertSame('2026-03-01', $affectations[0]->date_debut->toDateString());
        $this->assertSame('2026-08-31', $affectations[0]->date_fin->toDateString());
        $this->assertSame('2026-09-01', $affectations[1]->date_debut->toDateString());
        $this->assertSame('2027-02-28', $affectations[1]->date_fin->toDateString());

        $this->get("/api/affaires-sociales/arrets/{$id}/pdf-decision")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_simulation_classer_et_permissions(): void
    {
        $agent = $this->creerAgentAvecSalaire();
        $clinique = $this->creerStructure('Cabinet Kaya', TypeStructureSanitaire::MEDECIN);

        $id = $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::HONORAIRES_SOINS->value,
            'date_soins' => '2026-02-01',
            'structure_sanitaire_id' => $clinique->id,
            'montant_facture' => 25_000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.montant_calcule', 25_000)
            ->json('data.id');

        $this->getJson("/api/affaires-sociales/prises-en-charge/{$id}/simulation")
            ->assertOk()
            ->assertJsonPath('data.montant', 25_000)
            ->assertJsonPath('data.article_ccn', '123');

        $this->getJson("/api/affaires-sociales/agents/{$agent->id}/prises-en-charge")
            ->assertOk()
            ->assertJsonPath('data.0.id', $id);

        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/soumettre")->assertOk();
        $this->postJson("/api/affaires-sociales/prises-en-charge/{$id}/classer", [
            'commentaire' => 'Dossier incomplet',
        ])->assertOk()->assertJsonPath('data.statut', 'classee');

        Sanctum::actingAs($this->lecteur, ['*']);
        $this->getJson('/api/affaires-sociales/prises-en-charge')->assertOk();
        $this->getJson('/api/affaires-sociales/arrets')->assertOk();
        $this->postJson('/api/affaires-sociales/prises-en-charge', [
            'agent_id' => $agent->id,
            'type' => TypePriseEnCharge::HONORAIRES_SOINS->value,
            'date_soins' => '2026-02-02',
            'structure_sanitaire_id' => $clinique->id,
            'montant_facture' => 10_000,
        ])->assertForbidden();
    }

    public function test_codes_paie_sante_actifs(): void
    {
        foreach ([
            CodePaieElement::REMBOURSEMENT_SANTE,
            CodePaieElement::ALLOCATION_MALADIE,
            CodePaieElement::ALLOCATION_ACCIDENT_NON_PRO,
        ] as $code) {
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

    private function creerStructure(string $nom, TypeStructureSanitaire $type): StructureSanitaire
    {
        return StructureSanitaire::query()->create([
            'nom' => $nom,
            'type' => $type,
            'actif' => true,
        ]);
    }

    private function creerEnfant(Agent $agent, string $prenom = 'Enfant'): AyantDroit
    {
        return AyantDroit::query()->create([
            'agent_id' => $agent->id,
            'type' => TypeAyantDroit::ENFANT,
            'nom' => 'Test',
            'prenom' => $prenom,
            'date_naissance' => '2018-01-01',
            'lien_juridique' => 'mariage',
            'qualite_age' => 'standard',
            'actif' => true,
        ]);
    }

    private function creerAgentAvecSalaire(float $base = 100_000, string $prise = '2020-01-01'): Agent
    {
        $agent = Agent::query()->create([
            'nom' => 'Test',
            'prenom' => 'Sante',
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
