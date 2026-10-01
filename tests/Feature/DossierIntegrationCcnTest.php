<?php

namespace Tests\Feature;

use App\Enums\PieceCcnArt46;
use App\Enums\StatutDossier;
use App\Enums\TypeOrganismeSocial;
use App\Models\AffiliationSociale;
use App\Models\Agent;
use App\Models\DocumentDossier;
use App\Models\DossierIntegration;
use App\Models\OrganismeSocial;
use App\Models\SituationFamiliale;
use App\Models\TypeIntegration;
use App\Models\User;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\TypeIntegrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DossierIntegrationCcnTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([TypeDocumentSeeder::class, TypeIntegrationSeeder::class]);

        OrganismeSocial::create([
            'nom'   => 'Caisse Nationale de Sécurité Sociale',
            'code'  => 'CNSS',
            'type'  => TypeOrganismeSocial::CNSS,
            'actif' => true,
        ]);

        $this->rh = User::factory()->create();
        Sanctum::actingAs($this->rh, ['*']);
    }

    public function test_soumission_recrutement_externe_sans_ace_retourne_422(): void
    {
        $dossier = $this->creerDossier('Recrutement externe');

        $response = $this->postJson("/api/integration/dossiers/{$dossier->id}/soumettre")
            ->assertStatus(422);

        $this->assertStringContainsString(PieceCcnArt46::ACE->value, (string) $response->json('message'));
    }

    public function test_deja_salarie_sans_certificat_de_travail_retourne_422(): void
    {
        $dossier = $this->creerDossier('Recrutement externe', dejaSalarie: true);
        $this->deposerPivot($dossier);

        $response = $this->postJson("/api/integration/dossiers/{$dossier->id}/soumettre")
            ->assertStatus(422);

        $this->assertStringContainsString(PieceCcnArt46::CERTIFICAT_TRAVAIL->value, (string) $response->json('message'));
    }

    public function test_marie_sans_acte_de_mariage_retourne_422(): void
    {
        $agent = $this->creerAgent();
        SituationFamiliale::create([
            'agent_id'           => $agent->id,
            'statut_matrimonial' => 'marie',
            'nb_enfants'         => 0,
        ]);

        $dossier = $this->creerDossier('Recrutement externe', agent: $agent);
        $this->deposerPivot($dossier);

        $response = $this->postJson("/api/integration/dossiers/{$dossier->id}/soumettre")
            ->assertStatus(422);

        $this->assertStringContainsString(PieceCcnArt46::ACTE_MARIAGE->value, (string) $response->json('message'));
    }

    public function test_mutation_peut_etre_soumise_sans_ace(): void
    {
        $dossier = $this->creerDossier('Mutation');

        $this->postJson("/api/integration/dossiers/{$dossier->id}/soumettre")
            ->assertOk()
            ->assertJsonPath('data.statut', 'SOUMIS');
    }

    public function test_integrer_sans_numero_cnss_retourne_422(): void
    {
        $agent = $this->creerAgent();
        $dossier = $this->creerDossier('Recrutement externe', agent: $agent, statut: StatutDossier::VALIDE_DG);

        $this->postJson("/api/integration/dossiers/{$dossier->id}/integrer")
            ->assertStatus(422)
            ->assertJsonPath('errors.numero_cnss.0', 'Immatriculation CNSS obligatoire (art. 47).');
    }

    public function test_integrer_avec_numero_cnss_cree_affiliation(): void
    {
        $agent = $this->creerAgent();
        $dossier = $this->creerDossier('Recrutement externe', agent: $agent, statut: StatutDossier::VALIDE_DG);

        $this->postJson("/api/integration/dossiers/{$dossier->id}/integrer", [
            'numero_cnss' => '101234567',
        ])
            ->assertOk()
            ->assertJsonPath('data.dossier.statut', 'INTEGRE');

        $this->assertSame('101234567', $agent->fresh()->numero_cnss);
        $this->assertTrue(
            AffiliationSociale::query()->where('agent_id', $agent->id)->where('numero_affiliation', '101234567')->exists()
        );
    }

    public function test_integrer_stage_sans_cnss_reste_autorise(): void
    {
        $agent = $this->creerAgent();
        $dossier = $this->creerDossier('Stage professionnel', agent: $agent, statut: StatutDossier::VALIDE_DG);

        $this->postJson("/api/integration/dossiers/{$dossier->id}/integrer")
            ->assertOk()
            ->assertJsonPath('data.dossier.statut', 'INTEGRE');

        $this->assertNull($agent->fresh()->numero_cnss);
    }

    public function test_etat_documents_signale_ace_obligatoire_et_autorise_acte_mariage(): void
    {
        $dossier = $this->creerDossier('Recrutement externe');

        $etat = $this->getJson("/api/integration/dossiers/{$dossier->id}/documents")
            ->assertOk()
            ->json('data.manquants');

        $noms = collect($etat)->pluck('type_document.nom');
        $this->assertTrue($noms->contains(PieceCcnArt46::ACE->value));
        $this->assertTrue($noms->contains(PieceCcnArt46::ACTE_MARIAGE->value));

        $ace = collect($etat)->firstWhere('type_document.nom', PieceCcnArt46::ACE->value);
        $mariage = collect($etat)->firstWhere('type_document.nom', PieceCcnArt46::ACTE_MARIAGE->value);
        $this->assertTrue($ace['est_obligatoire']);
        $this->assertFalse($mariage['est_obligatoire']);
    }

    private function creerAgent(): Agent
    {
        return Agent::query()->create([
            'nom'            => 'Essai',
            'prenom'         => 'Ccn',
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'statut'         => 'actif',
        ]);
    }

    private function creerDossier(
        string $typeNom,
        bool $dejaSalarie = false,
        ?Agent $agent = null,
        StatutDossier $statut = StatutDossier::BROUILLON,
    ): DossierIntegration {
        $type = TypeIntegration::where('nom', $typeNom)->firstOrFail();

        return DossierIntegration::create([
            'reference'           => 'ARTF-INT-CCN-'.uniqid(),
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->rh->id,
            'agent_id'            => $agent?->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => $statut,
            'deja_salarie'        => $dejaSalarie,
        ]);
    }

    private function deposerPivot(DossierIntegration $dossier): void
    {
        $dossier->load('typeIntegration.documentsObligatoires');

        foreach ($dossier->typeIntegration->documentsObligatoires as $type) {
            DocumentDossier::create([
                'dossier_integration_id' => $dossier->id,
                'type_document_id'       => $type->id,
                'nom_original'           => 'piece.pdf',
                'chemin_fichier'         => "dossiers/{$dossier->id}/documents/{$type->id}.pdf",
                'est_obligatoire'        => true,
            ]);
        }
    }
}
