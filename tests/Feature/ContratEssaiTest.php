<?php

namespace Tests\Feature;

use App\Enums\StatutDossier;
use App\Enums\TypeChangementSalaireAgent;
use App\Jobs\ContratDelai30JoursJob;
use App\Jobs\ContratEssaiEnFinDateJob;
use App\Models\Agent;
use App\Models\Categorie;
use App\Models\DossierIntegration;
use App\Models\Echelon;
use App\Models\Grade;
use App\Models\SalaireAgent;
use App\Models\TypeContrat;
use App\Models\TypeIntegration;
use App\Models\User;
use App\Services\SalaireService;
use Database\Seeders\CategorieSeeder;
use Database\Seeders\ClassegrillesalarialeSeeder;
use Database\Seeders\EchelonSeeder;
use Database\Seeders\GradeSeeder;
use Database\Seeders\ParametregrileSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TypeContratSeeder;
use Database\Seeders\TypeDocumentSeeder;
use Database\Seeders\TypeIntegrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContratEssaiTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

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
            TypeContratSeeder::class,
        ]);

        app(SalaireService::class)->generateGrille(300.0);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($this->rh, ['*']);
    }

    public function test_essai_un_mois_pour_classe_i(): void
    {
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 3);
        $contrat = $this->creerContratHttp($agent, 'CDI', '2026-09-01');

        $contrat->assertCreated()
            ->assertJsonPath('data.essai.statut', 'en_cours')
            ->assertJsonPath('data.essai.duree_mois', 1)
            ->assertJsonPath('data.essai.date_debut', '2026-09-01')
            ->assertJsonPath('data.essai.date_fin', '2026-09-30')
            ->assertJsonPath('data.essai.peut_renouveler', true)
            ->assertJsonPath('data.essai.prochaine_etape', 'confirmer-essai');

        $salaire = SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first();
        $this->assertNotNull($salaire);
        $this->assertSame(1, $salaire->echelon);
        $this->assertEquals(147000.0, (float) $salaire->montant_base);
        $this->assertSame(3, $agent->fresh()->echelon->numero);
    }

    public function test_essai_deux_mois_pour_classe_v(): void
    {
        $agent = $this->creerAgent('Contrôleur', 'Classe V', 1);
        $this->creerContratHttp($agent, 'CDI', '2026-01-15')
            ->assertCreated()
            ->assertJsonPath('data.essai.duree_mois', 2)
            ->assertJsonPath('data.essai.date_fin', '2026-03-14');
    }

    public function test_essai_trois_mois_pour_classe_viii(): void
    {
        $agent = $this->creerAgent('Inspecteur', 'Classe VIII', 1);
        $this->creerContratHttp($agent, 'CDD', '2026-01-01')
            ->assertCreated()
            ->assertJsonPath('data.essai.duree_mois', 3)
            ->assertJsonPath('data.essai.date_fin', '2026-03-31');
    }

    public function test_stage_sans_periode_essai(): void
    {
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $this->creerContratHttp($agent, 'STG', '2026-09-01')
            ->assertCreated()
            ->assertJsonPath('data.essai.statut', 'non_applicable')
            ->assertJsonPath('data.essai.duree_mois', null);

        $this->assertNull(SalaireAgent::where('agent_id', $agent->id)->first());
    }

    public function test_deuxieme_renouvellement_refuse(): void
    {
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $id = $this->creerContratHttp($agent, 'CDI', '2026-09-01')->json('data.id');

        $this->postJson("/api/carriere/contrats/{$id}/renouveler-essai")
            ->assertOk()
            ->assertJsonPath('data.essai.statut', 'renouvele')
            ->assertJsonPath('data.essai.renouvele', true)
            ->assertJsonPath('data.essai.date_fin', '2026-10-30')
            ->assertJsonPath('data.essai.peut_renouveler', false);

        $this->postJson("/api/carriere/contrats/{$id}/renouveler-essai")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['essai']);
    }

    public function test_rompre_essai_resilie_sans_preavis(): void
    {
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $id = $this->creerContratHttp($agent, 'CDI', '2026-09-01')->json('data.id');

        $this->postJson("/api/carriere/contrats/{$id}/rompre-essai", [
            'commentaire' => 'Non concluant',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'resilie')
            ->assertJsonPath('data.essai.statut', 'rompu')
            ->assertJsonPath('data.essai.prochaine_etape', null);

        $this->assertNull(SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first());
    }

    public function test_confirmation_essai_passe_a_lechelon_cible(): void
    {
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 3);
        $id = $this->creerContratHttp($agent, 'CDI', '2026-09-01')->json('data.id');

        $this->postJson("/api/carriere/contrats/{$id}/confirmer-essai")
            ->assertOk()
            ->assertJsonPath('data.essai.statut', 'concluant')
            ->assertJsonPath('data.essai.prochaine_etape', null);

        $actuel = SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first();
        $this->assertNotNull($actuel);
        $this->assertSame(3, $actuel->echelon);
        $this->assertEquals(174000.0, (float) $actuel->montant_base);
        $this->assertSame(TypeChangementSalaireAgent::CONFIRMATION_ESSAI, $actuel->type_changement);
    }

    public function test_recrutement_externe_necessite_contrat_apres_seed(): void
    {
        $this->seed([TypeDocumentSeeder::class, TypeIntegrationSeeder::class]);

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();
        $this->assertTrue($type->necessite_contrat);

        $contractuel = TypeIntegration::where('nom', 'Contractuel')->firstOrFail();
        $this->assertTrue($contractuel->necessite_contrat);

        $mutation = TypeIntegration::where('nom', 'Mutation')->firstOrFail();
        $this->assertFalse($mutation->necessite_contrat);
    }

    public function test_alerte_delai_30_jours_apres_pds_sans_contrat(): void
    {
        $this->seed([TypeDocumentSeeder::class, TypeIntegrationSeeder::class]);

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $agent->update(['date_prise_service' => now()->subWeekdays(35)->toDateString()]);

        DossierIntegration::create([
            'reference'           => 'DOS-ESSAI-001',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->rh->id,
            'agent_id'            => $agent->id,
            'date_demande'        => now()->subMonths(2)->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);

        $this->getJson('/api/carriere/contrats/alertes/delai-30-jours')
            ->assertOk()
            ->assertJsonPath('data.0.agent_id', $agent->id);

        $this->assertGreaterThan(30, $this->getJson('/api/carriere/contrats/alertes/delai-30-jours')->json('data.0.jours_ouvrables'));
    }

    public function test_alerte_absente_si_contrat_cdi_actif(): void
    {
        $this->seed([TypeDocumentSeeder::class, TypeIntegrationSeeder::class]);

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $agent->update(['date_prise_service' => now()->subWeekdays(35)->toDateString()]);

        DossierIntegration::create([
            'reference'           => 'DOS-ESSAI-002',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->rh->id,
            'agent_id'            => $agent->id,
            'date_demande'        => now()->subMonths(2)->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);

        $this->creerContratHttp($agent, 'CDI', now()->toDateString())->assertCreated();

        $this->getJson('/api/carriere/contrats/alertes/delai-30-jours')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_alerte_absente_si_prise_de_service_recente(): void
    {
        $this->seed([TypeDocumentSeeder::class, TypeIntegrationSeeder::class]);

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $agent->update(['date_prise_service' => now()->subWeekdays(10)->toDateString()]);

        DossierIntegration::create([
            'reference'           => 'DOS-ESSAI-004',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->rh->id,
            'agent_id'            => $agent->id,
            'date_demande'        => now()->subWeeks(2)->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);

        $this->getJson('/api/carriere/contrats/alertes/delai-30-jours')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_jobs_essai_et_delai_notifient_la_rh(): void
    {
        $agent = $this->creerAgent('Personnel de service', 'Classe I', 1);
        $contratId = $this->creerContratHttp($agent, 'CDI', now()->toDateString())->json('data.id');

        $contrat = \App\Models\Contrat::findOrFail($contratId);
        $contrat->update(['date_fin_essai' => now()->addDays(7)->toDateString()]);

        (new ContratEssaiEnFinDateJob())->handle(app(\App\Services\ContratService::class));

        $this->assertSame(1, $this->rh->notifications()->count());
        $this->assertSame('essai_echeance', $this->rh->notifications()->first()->data['action']);

        $this->seed([TypeDocumentSeeder::class, TypeIntegrationSeeder::class]);
        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();
        $agentSansContrat = $this->creerAgent('Personnel de service', 'Classe I', 1, 'Sans', 'Contrat');
        $agentSansContrat->update(['date_prise_service' => now()->subWeekdays(40)->toDateString()]);
        DossierIntegration::create([
            'reference'           => 'DOS-ESSAI-003',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $this->rh->id,
            'agent_id'            => $agentSansContrat->id,
            'date_demande'        => now()->subMonths(2)->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);

        (new ContratDelai30JoursJob())->handle(app(\App\Services\ContratService::class));

        $actions = $this->rh->notifications()->get()->pluck('data.action');
        $this->assertTrue($actions->contains('contrat_delai_depasse'));
    }

    private function creerAgent(string $gradeNom, string $categorieNom, int $echelonNumero, string $nom = 'Essai', string $prenom = 'Agent'): Agent
    {
        return Agent::query()->create([
            'nom'            => $nom,
            'prenom'         => $prenom,
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'nationalite'    => 'Congolaise',
            'categorie_id'   => Categorie::where('nom', $categorieNom)->firstOrFail()->id,
            'grade_id'       => Grade::where('nom', $gradeNom)->firstOrFail()->id,
            'echelon_id'     => Echelon::where('numero', $echelonNumero)->firstOrFail()->id,
            'statut'         => 'actif',
        ]);
    }

    private function creerContratHttp(Agent $agent, string $sigle, string $dateDebut): \Illuminate\Testing\TestResponse
    {
        $type = TypeContrat::where('sigle', $sigle)->firstOrFail();

        return $this->postJson('/api/carriere/contrats', [
            'agent_id'         => $agent->id,
            'type_contrat_id'  => $type->id,
            'date_debut'       => $dateDebut,
            'lieu_recrutement' => 'Brazzaville',
        ]);
    }
}
