<?php

namespace Tests\Feature;

use App\Enums\MotifAffectation;
use App\Enums\MotifArchivage;
use App\Enums\StatutAffectation;
use App\Enums\StatutNomination;
use App\Models\Administration;
use App\Models\Agent;
use App\Models\Bureau;
use App\Models\Categorie;
use App\Models\Classegrillesalariale;
use App\Models\Direction;
use App\Models\Echelon;
use App\Models\Grade;
use App\Models\Localite;
use App\Models\Nomination;
use App\Models\SalaireAgent;
use App\Models\Service;
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
use Database\Seeders\TypeIntegrationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LotECcnTest extends TestCase
{
    use RefreshDatabase;

    private User $rh;

    private Direction $direction;

    private Service $service;

    private Bureau $bureau;

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
            TypeIntegrationSeeder::class,
        ]);

        app(SalaireService::class)->generateGrille(300.0);

        $this->rh = User::factory()->create();
        $this->rh->assignRole(Role::findByName('rh', 'api'));
        Sanctum::actingAs($this->rh, ['*']);

        $localite = Localite::create(['nom' => 'Brazzaville']);
        $admin    = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $this->direction = Direction::create(['nom' => 'Direction Test', 'administration_id' => $admin->id]);
        $this->service   = Service::create(['nom' => 'Service Test', 'direction_id' => $this->direction->id]);
        $this->bureau    = Bureau::create(['nom' => 'Bureau Test', 'service_id' => $this->service->id]);
    }

    public function test_archivage_diminution_activite_pose_priorite_deux_ans(): void
    {
        $agent = $this->creerAgentSimple('Mabiala', 'Jean');

        $this->postJson("/api/personnel/agents/{$agent->id}/archiver", [
            'motif'      => 'Compression d\'effectif',
            'motif_code' => MotifArchivage::DIMINUTION_ACTIVITE->value,
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', 'archive')
            ->assertJsonPath('data.motif_archivage_code', MotifArchivage::DIMINUTION_ACTIVITE->value)
            ->assertJsonPath('data.prioritaire_reembauche_jusquau', now()->addYears(2)->toDateString());
    }

    public function test_creation_recrutement_signale_homonyme_prioritaire(): void
    {
        $ancien = $this->creerAgentSimple('Mabiala', 'Jean', 'CNSS-48-001');
        $this->postJson("/api/personnel/agents/{$ancien->id}/archiver", [
            'motif'      => 'Réorganisation',
            'motif_code' => MotifArchivage::REORGANISATION->value,
        ])->assertOk();

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();

        $this->postJson('/api/integration/agents', [
            'nom'                 => 'Mabiala',
            'prenom'              => 'Jean',
            'date_naissance'      => '1992-03-04',
            'genre'               => 'M',
            'type_integration_id' => $type->id,
        ])
            ->assertCreated()
            ->assertJsonPath('meta.priorite_reembauche.priorite_reembauche', true)
            ->assertJsonPath('meta.priorite_reembauche.nouvel_essai_requis', false)
            ->assertJsonPath('meta.priorite_reembauche.agents.0.id', $ancien->id);
    }

    public function test_annee_supplementaire_exige_nouvel_essai(): void
    {
        $ancien = $this->creerAgentSimple('Ngoma', 'Paul', 'CNSS-48-002');
        $this->postJson("/api/personnel/agents/{$ancien->id}/archiver", [
            'motif'                   => 'Priorité manuelle',
            'motif_code'              => MotifArchivage::AUTRE->value,
            'prioritaire_reembauche'  => true,
        ])->assertOk();

        $ancien->update(['prioritaire_reembauche_jusquau' => now()->subDay()->toDateString()]);

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();

        $this->postJson('/api/integration/agents', [
            'nom'                 => 'Autre',
            'prenom'              => 'Candidat',
            'date_naissance'      => '1988-01-01',
            'genre'               => 'M',
            'numero_cnss'         => 'CNSS-48-002',
            'type_integration_id' => $type->id,
        ])
            ->assertCreated()
            ->assertJsonPath('meta.priorite_reembauche.priorite_reembauche', true)
            ->assertJsonPath('meta.priorite_reembauche.nouvel_essai_requis', true);
    }

    public function test_priorite_expiree_apres_trois_ans_non_signalee(): void
    {
        $ancien = $this->creerAgentSimple('Kaya', 'Luc');
        $this->postJson("/api/personnel/agents/{$ancien->id}/archiver", [
            'motif'      => 'Diminution',
            'motif_code' => MotifArchivage::DIMINUTION_ACTIVITE->value,
        ])->assertOk();

        $ancien->update(['prioritaire_reembauche_jusquau' => now()->subYear()->subDay()->toDateString()]);

        $type = TypeIntegration::where('nom', 'Recrutement externe')->firstOrFail();

        $this->postJson('/api/integration/agents', [
            'nom'                 => 'Kaya',
            'prenom'              => 'Luc',
            'date_naissance'      => '1991-06-06',
            'genre'               => 'M',
            'type_integration_id' => $type->id,
        ])
            ->assertCreated()
            ->assertJsonPath('meta.priorite_reembauche.priorite_reembauche', false);
    }

    public function test_essai_emploi_superieur_applique_minimum_classe_cible(): void
    {
        $agent = $this->creerAgentClasse('Personnel de service', 'Classe I', 3);
        $this->creerSalaire($agent);

        $premiere = $this->creerEtActiverNomination($agent, 'Chef de Bureau', Bureau::class, $this->bureau->id);
        $classeCible = $this->classeParGrade('Contrôleur');

        $essai = $this->creerEtActiverNomination(
            $agent,
            'Chef de Service',
            Service::class,
            $this->service->id,
            [
                'soumis_a_essai'           => true,
                'classegrillesalariale_id' => $classeCible->id,
            ]
        );

        $this->assertSame(StatutNomination::CLOTUREE, $premiere->fresh()->statut);
        $this->assertSame('en_cours', $essai->json('data.essai.statut'));
        $this->assertSame(2, $essai->json('data.essai.duree_mois'));
        $this->assertSame('2026-09-01', $essai->json('data.essai.date_debut'));
        $this->assertSame('2026-10-31', $essai->json('data.essai.date_fin'));
        $this->assertSame('confirmer-essai', $essai->json('data.essai.prochaine_etape'));

        $salaire = SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first();
        $this->assertNotNull($salaire);
        $this->assertSame($classeCible->id, $salaire->classegrillesalariale_id);
        $this->assertSame(1, $salaire->echelon);
        $this->assertSame('essai_emploi_superieur', $salaire->type_changement->value);
    }

    public function test_rompre_essai_retablit_nomination_et_salaire_precedents(): void
    {
        $agent = $this->creerAgentClasse('Personnel de service', 'Classe I', 3);
        $this->creerSalaire($agent);
        $classeInitiale = $this->classeParGrade('Personnel de service');

        $premiere = $this->creerEtActiverNomination($agent, 'Chef de Bureau', Bureau::class, $this->bureau->id);
        $essaiId = $this->creerEtActiverNomination(
            $agent,
            'Chef de Service',
            Service::class,
            $this->service->id,
            [
                'soumis_a_essai'           => true,
                'classegrillesalariale_id' => $this->classeParGrade('Contrôleur')->id,
            ]
        )->json('data.id');

        $this->postJson("/api/carriere/nominations/{$essaiId}/rompre-essai", [
            'commentaire' => 'Essai non concluant',
        ])
            ->assertOk()
            ->assertJsonPath('data.statut', StatutNomination::CLOTUREE->value)
            ->assertJsonPath('data.essai.statut', 'rompu');

        $this->assertSame(StatutNomination::ACTIVE, $premiere->fresh()->statut);

        $salaire = SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first();
        $this->assertSame($classeInitiale->id, $salaire->classegrillesalariale_id);
        $this->assertSame('restauration_essai', $salaire->type_changement->value);
    }

    public function test_confirmer_essai_conserve_la_classe_cible(): void
    {
        $agent = $this->creerAgentClasse('Personnel de service', 'Classe I', 3);
        $this->creerSalaire($agent);
        $classeCible = $this->classeParGrade('Contrôleur');

        $essaiId = $this->creerEtActiverNomination(
            $agent,
            'Chef de Service',
            Service::class,
            $this->service->id,
            [
                'soumis_a_essai'           => true,
                'classegrillesalariale_id' => $classeCible->id,
            ]
        )->json('data.id');

        $this->postJson("/api/carriere/nominations/{$essaiId}/confirmer-essai")
            ->assertOk()
            ->assertJsonPath('data.essai.statut', 'concluant')
            ->assertJsonPath('data.statut', StatutNomination::ACTIVE->value);

        $salaire = SalaireAgent::where('agent_id', $agent->id)->where('statut', 'actif')->first();
        $this->assertSame($classeCible->id, $salaire->classegrillesalariale_id);
    }

    public function test_essai_classe_non_superieure_retourne_422(): void
    {
        $agent = $this->creerAgentClasse('Contrôleur', 'Classe V', 1);

        $this->postJson('/api/carriere/nominations', [
            'agent_id'                 => $agent->id,
            'poste'                    => 'Chef de Service',
            'structurable_type'        => Service::class,
            'structurable_id'          => $this->service->id,
            'date_debut'               => '2026-09-01',
            'soumis_a_essai'           => true,
            'classegrillesalariale_id' => $this->classeParGrade('Personnel de service')->id,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['classegrillesalariale_id']);
    }

    public function test_rapprochement_sans_pieces_retourne_422(): void
    {
        $agent = $this->creerAgentSimple('Ndala', 'Marie');

        $this->postJson('/api/carriere/affectations', [
            'agent_id'          => $agent->id,
            'structurable_type' => Bureau::class,
            'structurable_id'   => $this->bureau->id,
            'date_affectation'  => '2026-09-01',
            'motif_code'        => MotifAffectation::RAPPROCHEMENT_CONJOINTS->value,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'piece_demande_manuscrite',
                'piece_acte_mariage',
                'piece_note_affectation_conjoint',
                'piece_attestation_residence',
            ]);
    }

    public function test_rapprochement_avec_pieces_ne_saccorde_pas_automatiquement(): void
    {
        Storage::fake('local');
        $agent = $this->creerAgentSimple('Ndala', 'Marie');

        $this->post('/api/carriere/affectations', [
            'agent_id'                        => $agent->id,
            'structurable_type'               => Bureau::class,
            'structurable_id'                 => $this->bureau->id,
            'date_affectation'                => '2026-09-01',
            'motif_code'                      => MotifAffectation::RAPPROCHEMENT_CONJOINTS->value,
            'commentaire_opportunite'         => 'Opportunité de service art. 82',
            'piece_demande_manuscrite'        => UploadedFile::fake()->create('demande.pdf', 20, 'application/pdf'),
            'piece_acte_mariage'              => UploadedFile::fake()->create('mariage.pdf', 20, 'application/pdf'),
            'piece_note_affectation_conjoint' => UploadedFile::fake()->create('note.pdf', 20, 'application/pdf'),
            'piece_attestation_residence'     => UploadedFile::fake()->create('residence.pdf', 20, 'application/pdf'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.statut', StatutAffectation::EN_ATTENTE_VALIDATION->value)
            ->assertJsonPath('data.motif_code', MotifAffectation::RAPPROCHEMENT_CONJOINTS->value)
            ->assertJsonPath('data.commentaire_opportunite', 'Opportunité de service art. 82');
    }

    private function creerAgentSimple(string $nom, string $prenom, ?string $cnss = null): Agent
    {
        return Agent::query()->create([
            'nom'            => $nom,
            'prenom'         => $prenom,
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'numero_cnss'    => $cnss,
            'statut'         => 'actif',
        ]);
    }

    private function creerAgentClasse(string $gradeNom, string $categorieNom, int $echelonNumero): Agent
    {
        return Agent::query()->create([
            'nom'            => 'Essai',
            'prenom'         => 'Superieur',
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'categorie_id'   => Categorie::where('nom', $categorieNom)->firstOrFail()->id,
            'grade_id'       => Grade::where('nom', $gradeNom)->firstOrFail()->id,
            'echelon_id'     => Echelon::where('numero', $echelonNumero)->firstOrFail()->id,
            'statut'         => 'actif',
        ]);
    }

    private function classeParGrade(string $nom): Classegrillesalariale
    {
        return Classegrillesalariale::query()
            ->whereHas('grade', fn ($q) => $q->where('nom', $nom))
            ->firstOrFail();
    }

    private function creerSalaire(Agent $agent): void
    {
        $this->postJson('/api/salaires-agents', ['agent_id' => $agent->id])->assertCreated();
    }

    private function creerEtActiverNomination(
        Agent $agent,
        string $poste,
        string $type,
        int $structureId,
        array $extra = [],
    ): \Illuminate\Testing\TestResponse|Nomination {
        $created = $this->postJson('/api/carriere/nominations', array_merge([
            'agent_id'          => $agent->id,
            'poste'             => $poste,
            'structurable_type' => $type,
            'structurable_id'   => $structureId,
            'date_debut'        => '2026-09-01',
            'type_acte'         => 'decision',
        ], $extra));
        $created->assertCreated();

        $id = $created->json('data.id');
        Nomination::query()->whereKey($id)->update(['statut' => StatutNomination::APPROUVEE]);

        $activated = $this->postJson("/api/carriere/nominations/{$id}/activer");
        $activated->assertOk();

        if ($extra === []) {
            return Nomination::findOrFail($id);
        }

        return $activated;
    }
}
