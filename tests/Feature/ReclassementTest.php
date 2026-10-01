<?php

namespace Tests\Feature;

use App\Enums\TypeChangementSalaireAgent;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Diplome;
use App\Models\Echelon;
use App\Models\Fonction;
use App\Models\InformationsProfessionnelle;
use App\Models\Salaire;
use App\Models\User;
use App\Services\SalaireAgentService;
use App\Services\SalaireService;
use Database\Seeders\CategorieSeeder;
use Database\Seeders\ClassegrillesalarialeSeeder;
use Database\Seeders\EchelonSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\ParametregrileSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReclassementTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;
    private User $dg;
    private SalaireAgentService $salaireService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            RoleSeeder::class,
            GradeSeeder::class,
            CategorieSeeder::class,
            EchelonSeeder::class,
            ClassegrillesalarialeSeeder::class,
            ParametregrileSeeder::class,
        ]);

        app(SalaireService::class)->generateGrille(300.0);
        $this->salaireService = app(SalaireAgentService::class);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));

        $this->dg = User::factory()->create();
        $this->dg->assignRole(Role::findByName('directeur-general', 'api'));
    }

    public function test_art_73_sans_diplome_est_refuse(): void
    {
        $agent = $this->agentEnClasse('Classe I', echelon: 1, prise: '2018-01-01');

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/reclassements', [
            'agent_id' => $agent->id,
            'type'     => 'reclassement_formation',
            'motif'    => 'Reclassement demandé suite à une formation diplômante.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.diplome_id.0', fn ($m) => str_contains($m, 'diplôme'));
    }

    public function test_art_73_rh_approuve_et_applique_change_la_classe(): void
    {
        $origine = $this->classe('Classe I');
        $cible   = $this->classe('Classe II');
        $agent   = $this->agentEnClasse('Classe I', echelon: 3, prise: '2018-01-01');
        $diplome = $this->diplomePourClasse($cible);
        $this->poserDiplomeAuDossier($agent, $diplome);

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/reclassements', [
            'agent_id'   => $agent->id,
            'type'       => 'reclassement_formation',
            'diplome_id' => $diplome->id,
            'motif'      => 'Formation autorisée et diplôme d\'État reconnu.',
        ])->assertCreated()
            ->assertJsonPath('data.statut', 'soumis')
            ->assertJsonPath('data.prochaine_etape', 'approuver')
            ->assertJsonPath('data.echelon_cible', 1)
            ->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/reclassements/{$id}/approuver")
            ->assertForbidden();

        Sanctum::actingAs($this->rh);
        $this->postJson("/api/carriere/reclassements/{$id}/approuver")
            ->assertOk()
            ->assertJsonPath('data.statut', 'approuve');

        $this->postJson("/api/carriere/reclassements/{$id}/appliquer")
            ->assertOk()
            ->assertJsonPath('data.statut', 'applique')
            ->assertJsonPath('meta.applique', true);

        $agent->refresh();
        $this->assertSame($cible->categorie_id, $agent->categorie_id);
        $this->assertSame($cible->grade_id, $agent->grade_id);

        $actuel = $this->salaireService->getActuel($agent->id);
        $this->assertSame($cible->id, $actuel->classegrillesalariale_id);
        $this->assertNotSame($origine->id, $actuel->classegrillesalariale_id);
        $this->assertSame(1, $actuel->echelon);
        $this->assertSame(TypeChangementSalaireAgent::RECLASSEMENT, $actuel->type_changement);

        $this->postJson("/api/carriere/reclassements/{$id}/appliquer")
            ->assertOk()
            ->assertJsonPath('meta.applique', false);
    }

    public function test_art_74a_age_49_est_refuse(): void
    {
        $agent = $this->agentEnClasse(
            'Classe I',
            echelon: 2,
            prise: '2008-01-01',
            naissance: '1977-03-01',
        );

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/reclassements', [
            'agent_id'        => $agent->id,
            'type'            => 'reclassement_exceptionnel',
            'classe_cible_id' => $this->classe('Classe II')->id,
            'motif'           => 'Demande de reclassement exceptionnel art. 74.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.age.0', fn ($m) => str_contains($m, '50'));
    }

    public function test_art_74a_eligible_dg_approuve_rh_applique(): void
    {
        $cible = $this->classe('Classe II');
        $agent = $this->agentEnClasse(
            'Classe I',
            echelon: 4,
            prise: '2008-01-01',
            naissance: '1970-01-01',
        );

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/reclassements', [
            'agent_id'        => $agent->id,
            'type'            => 'reclassement_exceptionnel',
            'classe_cible_id' => $cible->id,
            'motif'           => 'Agent de 50 ans et plus, quinze ans d\'ancienneté.',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->rh);
        $this->postJson("/api/carriere/reclassements/{$id}/approuver")
            ->assertForbidden();

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/reclassements/{$id}/approuver")
            ->assertOk()
            ->assertJsonPath('data.statut', 'approuve');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/reclassements/{$id}/appliquer")
            ->assertForbidden();

        Sanctum::actingAs($this->rh);
        $this->postJson("/api/carriere/reclassements/{$id}/appliquer")->assertOk();

        $actuel = $this->salaireService->getActuel($agent->id);
        $this->assertSame($cible->id, $actuel->classegrillesalariale_id);
        $this->assertSame(1, $actuel->echelon);
    }

    public function test_art_74b_echelon_7_est_refuse(): void
    {
        $agent = $this->agentEnClasse(
            'Classe IX',
            echelon: 7,
            prise: '1999-01-01',
            naissance: '1965-01-01',
        );

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/reclassements', [
            'agent_id' => $agent->id,
            'type'     => 'hors_classe',
            'motif'    => 'Hors classe demandé trop tôt dans l\'échelle.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.echelon.0', fn ($m) => str_contains($m, '8'));
    }

    public function test_art_74b_inspecteur_principal_echelon_8_et_25_ans(): void
    {
        $hors  = $this->classe('Classe X');
        $agent = $this->agentEnClasse(
            'Classe IX',
            echelon: 8,
            prise: '1999-01-01',
            naissance: '1965-01-01',
        );

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/reclassements', [
            'agent_id' => $agent->id,
            'type'     => 'hors_classe',
            'motif'    => 'Hors classe inspecteur principal au 8e échelon.',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/reclassements/{$id}/approuver")->assertOk();
        Sanctum::actingAs($this->rh);
        $this->postJson("/api/carriere/reclassements/{$id}/appliquer")->assertOk();

        $actuel = $this->salaireService->getActuel($agent->id);
        $this->assertSame($hors->id, $actuel->classegrillesalariale_id);
        $this->assertSame(TypeChangementSalaireAgent::HORS_CLASSE, $actuel->type_changement);
        $this->assertSame(1, $actuel->echelon);
    }

    public function test_art_74b_sans_ligne_de_grille_est_refuse(): void
    {
        $hors = $this->classe('Classe X');
        Salaire::where('classegrillesalariale_id', $hors->id)->delete();

        $agent = $this->agentEnClasse(
            'Classe IX',
            echelon: 8,
            prise: '1999-01-01',
            naissance: '1965-01-01',
        );

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/reclassements', [
            'agent_id' => $agent->id,
            'type'     => 'hors_classe',
            'motif'    => 'Hors classe sans barème généré pour la classe X.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.classe_cible_id.0', fn ($m) => str_contains($m, 'grille'));
    }

    public function test_art_75_maladie_sans_certificat_est_refusee(): void
    {
        $agent    = $this->agentEnClasse('Classe I', echelon: 1, prise: '2018-01-01');
        $fonction = Fonction::create(['nom' => 'Chargé d\'études']);

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/reclassements', [
            'agent_id'           => $agent->id,
            'type'               => 'reconversion',
            'motif_reconversion' => 'maladie',
            'fonction_cible_id'  => $fonction->id,
            'motif'              => 'Reconversion pour raison de santé constatée.',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.piece_path.0', fn ($m) => str_contains($m, 'certificat'));
    }

    public function test_art_75_applique_la_fonction_sans_changer_seulement_lechelon(): void
    {
        $fonction = Fonction::create(['nom' => 'Assistant administratif']);
        $agent    = $this->agentEnClasse('Classe I', echelon: 3, prise: '2018-01-01');
        $classe   = $this->salaireService->getActuel($agent->id)->classegrillesalariale_id;
        $echelon  = $this->salaireService->getActuel($agent->id)->echelon;

        Sanctum::actingAs($this->rh);
        $id = $this->postJson('/api/carriere/reclassements', [
            'agent_id'           => $agent->id,
            'type'               => 'reconversion',
            'motif_reconversion' => 'reorganisation',
            'fonction_cible_id'  => $fonction->id,
            'motif'              => 'Réorganisation interne du service d\'affectation.',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->dg);
        $this->postJson("/api/carriere/reclassements/{$id}/approuver")->assertOk();
        Sanctum::actingAs($this->rh);
        $this->postJson("/api/carriere/reclassements/{$id}/appliquer")->assertOk();

        $agent->refresh();
        $this->assertSame($fonction->id, $agent->fonction_id);
        $actuel = $this->salaireService->getActuel($agent->id);
        $this->assertSame($classe, $actuel->classegrillesalariale_id);
        $this->assertSame($echelon, $actuel->echelon);
    }

    public function test_historique_par_agent_et_liste(): void
    {
        $cible   = $this->classe('Classe II');
        $agent   = $this->agentEnClasse('Classe I', echelon: 1, prise: '2018-01-01');
        $diplome = $this->diplomePourClasse($cible);
        $this->poserDiplomeAuDossier($agent, $diplome);

        Sanctum::actingAs($this->rh);
        $this->postJson('/api/carriere/reclassements', [
            'agent_id'   => $agent->id,
            'type'       => 'reclassement_formation',
            'diplome_id' => $diplome->id,
            'motif'      => 'Formation autorisée et diplôme d\'État reconnu.',
        ])->assertCreated();

        $this->getJson("/api/carriere/agents/{$agent->id}/reclassements")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/carriere/reclassements?type=reclassement_formation')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    private function agentEnClasse(
        string $categorieNom,
        int $echelon,
        string $prise,
        string $naissance = '1980-01-01',
    ): Agent {
        $classe     = $this->classe($categorieNom);
        $echelonMod = Echelon::where('numero', $echelon)->firstOrFail();

        $agent = Agent::query()->create([
            'nom'                => 'Test',
            'prenom'             => $categorieNom,
            'date_naissance'     => $naissance,
            'genre'              => 'M',
            'categorie_id'       => $classe->categorie_id,
            'grade_id'           => $classe->grade_id,
            'echelon_id'         => $echelonMod->id,
            'date_prise_service' => $prise,
            'statut'             => 'actif',
        ]);

        $this->salaireService->creerSalaireInitial($agent);

        return $agent->fresh();
    }

    private function classe(string $categorieNom): Classegrillesalariale
    {
        $categorie = Categorie::where('nom', $categorieNom)->firstOrFail();

        return Classegrillesalariale::where('categorie_id', $categorie->id)
            ->with(['grade', 'categorie'])
            ->firstOrFail();
    }

    private function diplomePourClasse(Classegrillesalariale $classe): Diplome
    {
        return Diplome::create([
            'nom'                      => 'Diplôme reclassement '.$classe->id,
            'sigle'                    => 'DR'.$classe->id,
            'classegrillesalariale_id' => $classe->id,
        ]);
    }

    private function poserDiplomeAuDossier(Agent $agent, Diplome $diplome): void
    {
        InformationsProfessionnelle::create([
            'agent_id'   => $agent->id,
            'diplome_id' => $diplome->id,
        ]);
    }
}
