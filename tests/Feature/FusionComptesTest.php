<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\CompteIntegration;
use App\Models\User;
use App\Services\CompteFusionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Fusion des comptes en double (commande `comptes:fusionner`).
 */
class FusionComptesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('agent', 'api');
        Role::findOrCreate('rh-formation', 'api');
    }

    public function test_le_bilan_seul_n_ecrit_rien(): void
    {
        [$orphelin, $conserve] = $this->paireSuffixee();
        $avant = User::query()->orderBy('id')->get(['id', 'email', 'agent_id'])->toArray();

        $this->artisan('comptes:fusionner')
            ->expectsOutputToContain('1 paire(s) à fusionner')
            ->expectsOutputToContain('Simple bilan')
            ->assertSuccessful();

        $this->assertSame($avant, User::query()->orderBy('id')->get(['id', 'email', 'agent_id'])->toArray());
        $this->assertDatabaseHas('users', ['id' => $orphelin->id]);
        $this->assertDatabaseHas('users', ['id' => $conserve->id, 'email' => 'remy.mampouya.ab123@artf.cg']);
    }

    public function test_fusion_par_adresse_suffixee_recupere_l_adresse_simple_et_l_historique(): void
    {
        [$orphelin, $conserve, $agent] = $this->paireSuffixee();
        $orphelin->assignRole('rh-formation');

        $logId = DB::table('audit_logs')->insertGetId([
            'user_id' => $orphelin->id, 'action' => 'connexion', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(), 'type' => 'test', 'notifiable_type' => User::class,
            'notifiable_id' => $orphelin->id, 'data' => '{}', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $ancienMotDePasse = $conserve->password;

        $this->artisan('comptes:fusionner --appliquer')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $orphelin->id]);
        $conserve->refresh();
        $this->assertSame('remy.mampouya@artf.cg', $conserve->email);
        $this->assertSame($agent->id, $conserve->agent_id);
        $this->assertSame($ancienMotDePasse, $conserve->password);
        $this->assertTrue($conserve->hasRole('rh-formation'));
        $this->assertTrue($conserve->hasRole('agent'));

        $this->assertSame($conserve->id, (int) DB::table('audit_logs')->where('id', $logId)->value('user_id'));
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $conserve->id)->count());
        $this->assertDatabaseHas('comptes_integration', [
            'agent_id' => $agent->id, 'user_id' => $conserve->id,
            'login' => 'remy.mampouya@artf.cg', 'email_professionnel' => 'remy.mampouya@artf.cg',
        ]);
        $this->assertSame('remy.mampouya@artf.cg', $agent->fresh()->email_professionnel);
    }

    public function test_fusion_par_nom_garde_l_adresse_du_compte_conserve(): void
    {
        $agent = $this->agent('MAMPOUYA', 'Rémy', 'AB123');
        $conserve = $this->compteAvecFiche($agent, 'remy.mampouya.ab123@artf.cg');
        $orphelin = $this->compteSansFiche('Remy MAMPOUYA', 'remy.perso@gmail.com');

        $this->artisan('comptes:fusionner --appliquer')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $orphelin->id]);
        $this->assertSame('remy.mampouya.ab123@artf.cg', $conserve->fresh()->email);
    }

    public function test_plusieurs_comptes_sur_la_meme_fiche_sont_regroupes_sur_celui_de_l_integration(): void
    {
        $agent = $this->agent('NGOUBILI', 'Paul', 'CD456');
        $doublon = $this->compteAvecFiche($agent, 'paul.ngoubili.cd456@artf.cg', integration: false);
        $integration = $this->compteAvecFiche($agent, 'paul.ngoubili@artf.cg');

        $this->artisan('comptes:fusionner --appliquer')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $doublon->id]);
        $this->assertSame('paul.ngoubili@artf.cg', $integration->fresh()->email);
        $this->assertSame(1, User::query()->where('agent_id', $agent->id)->count());
    }

    public function test_une_correspondance_ambigue_n_est_jamais_fusionnee(): void
    {
        $premier = $this->compteAvecFiche($this->agent('NGOMA', 'Jean', 'X1', '1980-01-01'), 'jean.ngoma.x1@artf.cg');
        $second = $this->compteAvecFiche($this->agent('NGOMA', 'Jean', 'X2', '1990-01-01'), 'jean.ngoma.x2@artf.cg');
        $orphelin = $this->compteSansFiche('Jean NGOMA', 'jean.ngoma@artf.cg');

        $bilan = app(CompteFusionService::class)->bilan();
        $this->assertSame([], $bilan['paires']);
        $this->assertSame($orphelin->id, $bilan['ambigues'][0]['compte']->id);

        $this->artisan('comptes:fusionner --appliquer')->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $orphelin->id]);
        $this->assertSame('jean.ngoma.x1@artf.cg', $premier->fresh()->email);
        $this->assertSame('jean.ngoma.x2@artf.cg', $second->fresh()->email);
    }

    public function test_un_nom_plus_long_n_est_pas_pris_pour_une_adresse_suffixee(): void
    {
        $this->compteAvecFiche($this->agent('BA NDONGO', 'Jean', 'Z9'), 'jean.ba.ndongo@artf.cg');
        $orphelin = $this->compteSansFiche('Jean BA', 'jean.ba@artf.cg');

        $bilan = app(CompteFusionService::class)->bilan();

        $this->assertSame([], $bilan['paires']);
        $this->assertSame([$orphelin->id], array_map(fn (User $u) => $u->id, $bilan['sans_correspondant']));
    }

    public function test_les_comptes_systeme_ne_sont_jamais_touches(): void
    {
        $admin = $this->compteSansFiche('Administrateur ARFT', 'admin@artf.cg');
        $agent = $this->agent('ADMIN', 'Administrateur', 'AD1');
        $this->compteAvecFiche($agent, 'administrateur.admin.ad1@artf.cg');

        $bilan = app(CompteFusionService::class)->bilan();
        $this->assertSame([], $bilan['paires']);
        $this->assertSame([], $bilan['sans_correspondant']);

        $this->artisan('comptes:fusionner --appliquer --paire='.$admin->id.':'.User::query()->whereNotNull('agent_id')->value('id'))
            ->assertFailed();
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'email' => 'admin@artf.cg']);
    }

    public function test_une_paire_forcee_invalide_est_annulee_sans_bloquer_les_autres(): void
    {
        [$orphelin, $conserve] = $this->paireSuffixee();
        $autreAgent = $this->agent('TSIBA', 'Marc', 'EF789');
        $autre = $this->compteAvecFiche($autreAgent, 'marc.tsiba@artf.cg');

        // Le compte absorbé appartient à une autre fiche : refus, rien ne bouge.
        $this->artisan("comptes:fusionner --appliquer --paire={$autre->id}:{$conserve->id} --paire={$orphelin->id}:{$conserve->id}")
            ->expectsOutputToContain('rattaché à une autre fiche agent')
            ->assertFailed();

        $this->assertDatabaseHas('users', ['id' => $autre->id, 'agent_id' => $autreAgent->id, 'email' => 'marc.tsiba@artf.cg']);
        $this->assertDatabaseMissing('users', ['id' => $orphelin->id]);
        $this->assertSame('remy.mampouya@artf.cg', $conserve->fresh()->email);
    }

    public function test_l_import_rattache_l_ancien_compte_au_lieu_d_en_creer_un_second(): void
    {
        $orphelin = $this->compteSansFiche('Rémy MAMPOUYA', 'remy.mampouya@artf.cg');
        $agent = $this->agent('MAMPOUYA', 'Rémy', 'AB123');

        $service = app(CompteFusionService::class);
        $this->assertSame($orphelin->id, $service->compteOrphelinPour($agent)?->id);

        // Homonyme : on ne devine pas.
        $this->agent('MAMPOUYA', 'Rémy', 'AB999', '1999-09-09');
        $this->assertNull(app(CompteFusionService::class)->compteOrphelinPour($agent));
    }

    public function test_la_purge_retire_les_comptes_de_demonstration_sans_toucher_aux_autres(): void
    {
        [$orphelin, $conserve] = $this->paireSuffixee();
        $demoVierge = $this->compteSansFiche('Fernand KIBANGOU', 'fernand.kibangou@artf.cg');
        $demoAvecHistorique = $this->compteSansFiche('Solange LOEMBA', 'solange.loemba@artf.cg');
        DB::table('audit_logs')->insert([
            'user_id' => $demoAvecHistorique->id, 'action' => 'connexion', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $actifSansFiche = User::factory()->create(['name' => 'Consultant', 'email' => 'consultant@artf.cg', 'is_active' => true]);
        $admin = $this->compteSansFiche('Administrateur ARFT', 'admin@artf.cg');

        // Sans --appliquer : rien ne bouge.
        $this->artisan('comptes:fusionner --purger')
            ->expectsOutputToContain('2 compte(s) désactivé(s) sans correspondant seraient retirés')
            ->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $demoVierge->id]);

        $this->artisan('comptes:fusionner --appliquer --purger')->assertSuccessful();

        $this->assertDatabaseMissing('users', ['id' => $orphelin->id]);
        $this->assertSame('remy.mampouya@artf.cg', $conserve->fresh()->email);
        $this->assertDatabaseMissing('users', ['id' => $demoVierge->id]);
        $this->assertDatabaseHas('users', ['id' => $demoAvecHistorique->id, 'is_active' => false]);
        $this->assertDatabaseHas('users', ['id' => $actifSansFiche->id, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_la_purge_refuse_un_compte_actif_ou_avec_fiche(): void
    {
        $service = app(CompteFusionService::class);
        $actif = User::factory()->create(['email' => 'consultant@artf.cg', 'is_active' => true]);
        $avecFiche = $this->compteAvecFiche($this->agent('TSIBA', 'Marc', 'EF789'), 'marc.tsiba@artf.cg');

        foreach ([$actif, $avecFiche] as $compte) {
            try {
                $service->purger($compte->id);
                $this->fail("Le compte {$compte->email} n'aurait pas dû être purgé.");
            } catch (\InvalidArgumentException) {
                $this->assertDatabaseHas('users', ['id' => $compte->id]);
            }
        }
    }

    // ─── Données ─────────────────────────────────────────────────────────────

    /** @return array{0: User, 1: User, 2: Agent} orphelin, conservé, agent */
    private function paireSuffixee(): array
    {
        $agent = $this->agent('MAMPOUYA', 'Rémy', 'AB123');
        $conserve = $this->compteAvecFiche($agent, 'remy.mampouya.ab123@artf.cg');
        $orphelin = $this->compteSansFiche('Rémy MAMPOUYA', 'remy.mampouya@artf.cg');

        return [$orphelin, $conserve, $agent];
    }

    private function agent(string $nom, string $prenom, ?string $matricule, string $naissance = '1985-05-05'): Agent
    {
        return Agent::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'matricule' => $matricule,
            'date_naissance' => $naissance,
            'genre' => 'M',
            'statut' => 'actif',
        ]);
    }

    private function compteAvecFiche(Agent $agent, string $email, bool $integration = true): User
    {
        $user = User::factory()->create([
            'name' => trim($agent->prenom.' '.$agent->nom),
            'email' => $email,
            'agent_id' => $agent->id,
            'is_active' => true,
        ]);
        $user->assignRole('agent');

        if ($integration) {
            CompteIntegration::query()->create([
                'agent_id' => $agent->id,
                'user_id' => $user->id,
                'login' => $email,
                'email_professionnel' => $email,
                'badge_numero' => $agent->matricule,
            ]);
            $agent->update(['email_professionnel' => $email]);
        }

        return $user;
    }

    /** Compte neutralisé par l'import : sans fiche, désactivé. */
    private function compteSansFiche(string $nom, string $email): User
    {
        return User::factory()->create([
            'name' => $nom,
            'email' => $email,
            'agent_id' => null,
            'is_active' => false,
        ]);
    }
}
