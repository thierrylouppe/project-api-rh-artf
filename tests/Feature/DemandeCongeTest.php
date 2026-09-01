<?php

namespace Tests\Feature;

use App\Enums\StatutAbsence;
use App\Enums\StatutAffectation;
use App\Enums\StatutDemandeConge;
use App\Models\Administration;
use App\Models\Affectation;
use App\Models\Agent;
use App\Models\Direction;
use App\Models\JourFerie;
use App\Models\Localite;
use App\Models\RegleAcquisitionConge;
use App\Models\TypeAbsence;
use App\Models\TypeConge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemandeCongeTest extends TestCase
{
    use RefreshDatabase;

    private User $demandeur;

    private User $chefUser;

    private User $rhUser;

    private User $dgUser;

    private Agent $agent;

    private Agent $chef;

    private TypeConge $annuel;

    private Direction $direction;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'consulter-conges', 'creer-conges', 'valider-conges',
            'consulter-absences', 'creer-absences', 'valider-absences',
        ] as $name) {
            Permission::findOrCreate($name, 'api');
        }

        Role::findOrCreate('rh', 'api');
        Role::findOrCreate('chef-service', 'api');
        Role::findOrCreate('directeur-general', 'api');
        Role::findOrCreate('admin', 'api');

        $this->agent = $this->creerAgent('Jean', 'Agent');
        $this->chef  = $this->creerAgent('Marie', 'Chef');

        $this->demandeur = User::factory()->create(['agent_id' => $this->agent->id]);
        $this->demandeur->givePermissionTo(['consulter-conges', 'creer-conges', 'consulter-absences', 'creer-absences', 'valider-absences']);

        $this->chefUser = User::factory()->create(['agent_id' => $this->chef->id]);
        $this->chefUser->givePermissionTo(['consulter-conges', 'valider-conges']);
        $this->chefUser->assignRole('chef-service');

        $this->rhUser = User::factory()->create();
        $this->rhUser->givePermissionTo(['consulter-conges', 'valider-conges']);
        $this->rhUser->assignRole('rh');

        $this->dgUser = User::factory()->create();
        $this->dgUser->givePermissionTo(['consulter-conges', 'valider-conges']);
        $this->dgUser->assignRole('directeur-general');

        $localite = Localite::create(['nom' => 'Brazzaville']);
        $admin    = Administration::create(['nom' => 'ARTF', 'localite_id' => $localite->id]);
        $this->direction = Direction::create(['nom' => 'DRHL', 'administration_id' => $admin->id]);

        Affectation::create([
            'agent_id'                   => $this->agent->id,
            'structurable_type'          => Direction::class,
            'structurable_id'            => $this->direction->id,
            'superieur_hierarchique_id'  => $this->chef->id,
            'date_affectation'           => '2026-01-01',
            'statut'                     => StatutAffectation::ACTIVE,
            'created_by'                 => $this->demandeur->id,
        ]);

        $this->annuel = TypeConge::create([
            'nom'          => 'Congé annuel',
            'jours_max'    => 30,
            'necessite_n1' => true,
            'necessite_rh' => true,
            'necessite_dg' => false,
            'debite_solde' => true,
        ]);

        RegleAcquisitionConge::create([
            'type_conge_id'  => $this->annuel->id,
            'jours_par_mois' => 2.5,
            'jours_max'      => 30,
        ]);

        Sanctum::actingAs($this->demandeur);
    }

    public function test_soumission_calcule_jours_ouvrables_hors_week_end_et_ferie(): void
    {
        JourFerie::create([
            'nom'       => 'Férié test',
            'date'      => '2026-09-08',
            'recurrent' => false,
        ]);

        $this->postJson('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $this->annuel->id,
            'date_debut'    => '2026-09-07',
            'date_fin'      => '2026-09-11',
            'motif'         => 'Repos',
        ])
            ->assertCreated()
            ->assertJsonPath('data.nb_jours', 4)
            ->assertJsonPath('data.statut', StatutDemandeConge::SOUMISE->value)
            ->assertJsonPath('data.prochaine_etape', 'valider-n1');
    }

    public function test_week_end_seul_retourne_422(): void
    {
        $this->postJson('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $this->annuel->id,
            'date_debut'    => '2026-09-12',
            'date_fin'      => '2026-09-13',
        ])->assertStatus(422);
    }

    public function test_workflow_n1_doit_etre_le_superieur_puis_rh_debite(): void
    {
        $id = $this->postJson('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $this->annuel->id,
            'date_debut'    => '2026-09-07',
            'date_fin'      => '2026-09-09',
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-n1")->assertForbidden();

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-n1")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_N1->value);
        $this->postJson("/api/conges/demandes/{$id}/valider-rh")->assertForbidden();

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_RH->value);

        Sanctum::actingAs($this->demandeur);
        $this->getJson("/api/conges/agents/{$this->agent->id}/soldes?annee=2026")
            ->assertOk()
            ->assertJsonPath('data.0.solde_actuel', 27);

        $this->getJson("/api/conges/demandes/{$id}/attestation")->assertOk();
    }

    public function test_maternite_rh_directe_sans_solde_avec_justificatif(): void
    {
        $type = TypeConge::create([
            'nom'                 => 'Congé de maternité',
            'jours_max'           => 98,
            'necessite_n1'        => false,
            'necessite_rh'        => true,
            'necessite_dg'        => false,
            'debite_solde'        => false,
            'justificatif_requis' => true,
        ]);

        $this->postJson('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $type->id,
            'date_debut'    => '2026-09-07',
            'date_fin'      => '2026-09-09',
        ])->assertStatus(422);

        $id = $this->post('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $type->id,
            'date_debut'    => '2026-09-07',
            'date_fin'      => '2026-09-09',
            'justificatif'  => UploadedFile::fake()->create('certificat.pdf', 80, 'application/pdf'),
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-n1")->assertStatus(422);

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_RH->value);

        $this->getJson("/api/conges/agents/{$this->agent->id}/soldes?annee=2026")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_sans_solde_passe_par_dg(): void
    {
        $type = TypeConge::create([
            'nom'                 => 'Congé sans solde',
            'jours_max'           => 90,
            'necessite_n1'        => true,
            'necessite_rh'        => true,
            'necessite_dg'        => true,
            'debite_solde'        => false,
            'justificatif_requis' => true,
        ]);

        $id = $this->post('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $type->id,
            'date_debut'    => '2026-09-07',
            'date_fin'      => '2026-09-09',
            'justificatif'  => UploadedFile::fake()->create('demande.pdf', 20, 'application/pdf'),
        ])->assertCreated()->json('data.id');

        Sanctum::actingAs($this->chefUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-n1")->assertOk();

        Sanctum::actingAs($this->rhUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-rh")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_RH->value);

        $this->getJson("/api/conges/demandes/{$id}/attestation")->assertStatus(422);

        Sanctum::actingAs($this->dgUser);
        $this->postJson("/api/conges/demandes/{$id}/valider-dg")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutDemandeConge::VALIDEE_DG->value);

        Sanctum::actingAs($this->demandeur);
        $this->getJson("/api/conges/demandes/{$id}/attestation")->assertOk();
    }

    public function test_solde_insuffisant_uniquement_si_debite_solde(): void
    {
        $court = TypeConge::create([
            'nom'          => 'Annuel court',
            'jours_max'    => 1,
            'debite_solde' => true,
        ]);

        $this->postJson('/api/conges/demandes', [
            'agent_id'      => $this->agent->id,
            'type_conge_id' => $court->id,
            'date_debut'    => '2026-09-07',
            'date_fin'      => '2026-09-09',
        ])->assertStatus(422);
    }

    public function test_absence_workflow(): void
    {
        $type = TypeAbsence::create([
            'nom'                   => 'Permission',
            'justification_requise' => true,
        ]);

        $id = $this->postJson('/api/absences', [
            'agent_id'        => $this->agent->id,
            'type_absence_id' => $type->id,
            'date_debut'      => '2026-09-07',
            'date_fin'        => '2026-09-07',
            'motif'           => 'RDV administratif',
        ])
            ->assertCreated()
            ->assertJsonPath('data.nb_jours', 1)
            ->assertJsonPath('data.statut', StatutAbsence::EN_ATTENTE->value)
            ->json('data.id');

        $this->postJson("/api/absences/{$id}/valider")
            ->assertOk()
            ->assertJsonPath('data.statut', StatutAbsence::VALIDEE->value);
    }

    private function creerAgent(string $prenom, string $nom): Agent
    {
        return Agent::create([
            'nom'            => $nom,
            'prenom'         => $prenom,
            'date_naissance' => '1990-01-01',
            'genre'          => 'M',
            'statut'         => 'actif',
        ]);
    }
}
