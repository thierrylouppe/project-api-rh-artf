<?php

namespace Tests\Feature;

use App\Enums\StatutConventionStage;
use App\Enums\StatutDossier;
use App\Enums\TypeStage;
use App\Jobs\ConventionStageEnFinDateJob;
use App\Models\Agent;
use App\Models\ConventionStage;
use App\Models\DossierIntegration;
use App\Models\TypeIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConventionStageEnFinDateJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifie_tuteur_agent_et_rh_a_j_moins_15(): void
    {
        Role::create(['name' => 'rh', 'guard_name' => 'api']);

        $rh = User::factory()->create();
        $rh->assignRole('rh');

        $stagiaire = Agent::create([
            'nom'            => 'Stagiaire',
            'prenom'         => 'Paul',
            'date_naissance' => '2000-01-01',
            'genre'          => 'M',
            'statut'         => 'stagiaire',
        ]);
        $tuteur = Agent::create([
            'nom'            => 'Tuteur',
            'prenom'         => 'Marie',
            'date_naissance' => '1980-01-01',
            'genre'          => 'F',
            'statut'         => 'actif',
        ]);

        $compteStagiaire = User::factory()->create(['agent_id' => $stagiaire->id]);
        $compteTuteur    = User::factory()->create(['agent_id' => $tuteur->id]);

        $type = TypeIntegration::create(['nom' => 'Stage professionnel test']);
        $dossier = DossierIntegration::create([
            'reference'           => 'DOS-STAGE-001',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $rh->id,
            'agent_id'            => $stagiaire->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);

        ConventionStage::create([
            'agent_id'               => $stagiaire->id,
            'dossier_integration_id' => $dossier->id,
            'tuteur_interne_id'      => $tuteur->id,
            'type_stage'             => TypeStage::PROFESSIONNEL,
            'etablissement'          => 'Université Test',
            'date_debut'             => now()->subMonths(5)->toDateString(),
            'date_fin'               => now()->addDays(15)->toDateString(),
            'statut_stage'           => StatutConventionStage::EN_COURS,
        ]);

        (new ConventionStageEnFinDateJob())->handle(
            app(\App\Interfaces\ConventionStageInterface::class),
            app(\App\Services\NotificationService::class),
            app(\App\Interfaces\UserInterface::class),
        );

        $this->assertSame(1, $compteStagiaire->notifications()->count());
        $this->assertSame(1, $compteTuteur->notifications()->count());
        $this->assertSame(1, $rh->notifications()->count());
        $this->assertSame('echeance', $rh->notifications()->first()->data['action']);
        $this->assertSame('stage', $rh->notifications()->first()->data['domaine']);
    }

    public function test_ignore_les_stages_hors_seuil(): void
    {
        $stagiaire = Agent::create([
            'nom'            => 'Hors',
            'prenom'         => 'Seuil',
            'date_naissance' => '2000-01-01',
            'genre'          => 'M',
            'statut'         => 'stagiaire',
        ]);
        $compte = User::factory()->create(['agent_id' => $stagiaire->id]);

        $type = TypeIntegration::create(['nom' => 'Stage hors seuil']);
        $dossier = DossierIntegration::create([
            'reference'           => 'DOS-STAGE-002',
            'type_integration_id' => $type->id,
            'demandeur_id'        => $compte->id,
            'agent_id'            => $stagiaire->id,
            'date_demande'        => now()->toDateString(),
            'statut'              => StatutDossier::INTEGRE,
        ]);

        ConventionStage::create([
            'agent_id'               => $stagiaire->id,
            'dossier_integration_id' => $dossier->id,
            'type_stage'             => TypeStage::PROFESSIONNEL,
            'etablissement'          => 'Université Test',
            'date_debut'             => now()->subMonth()->toDateString(),
            'date_fin'               => now()->addDays(30)->toDateString(),
            'statut_stage'           => StatutConventionStage::EN_COURS,
        ]);

        (new ConventionStageEnFinDateJob())->handle(
            app(\App\Interfaces\ConventionStageInterface::class),
            app(\App\Services\NotificationService::class),
            app(\App\Interfaces\UserInterface::class),
        );

        $this->assertSame(0, $compte->notifications()->count());
    }
}
