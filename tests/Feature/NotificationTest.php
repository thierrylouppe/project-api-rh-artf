<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\RhEvenementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_inbox_vide_au_depart(): void
    {
        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.non_lues', 0)
            ->assertJsonCount(0, 'data');
    }

    public function test_lister_marquer_lu_et_tout_lire(): void
    {
        $this->user->notify(new RhEvenementNotification(
            'integration',
            'validee_rh',
            'Dossier validé.',
            ['dossier_id' => 1]
        ));
        $this->user->notify(new RhEvenementNotification(
            'affectation',
            'creee',
            'Affectation créée.',
        ));

        $liste = $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.non_lues', 2);

        $id = collect($liste->json('data'))->firstWhere('domaine', 'integration')['id'];

        $this->getJson('/api/notifications/non-lues')
            ->assertOk()
            ->assertJsonPath('meta.non_lues', 2);

        $this->postJson("/api/notifications/{$id}/lu")
            ->assertOk()
            ->assertJsonPath('message', 'Notification marquée comme lue');

        $this->getJson('/api/notifications/non-lues')
            ->assertOk()
            ->assertJsonPath('meta.non_lues', 1);

        $this->postJson('/api/notifications/tout-lire')
            ->assertOk()
            ->assertJsonPath('data.mises_a_jour', 1);

        $this->getJson('/api/notifications/non-lues')
            ->assertOk()
            ->assertJsonPath('meta.non_lues', 0);
    }

    public function test_marquer_lu_une_notification_d_autrui_retourne_404(): void
    {
        $autre = User::factory()->create();
        $autre->notify(new RhEvenementNotification('integration', 'rejetee', 'Rejeté.'));

        $id = $autre->notifications()->first()->id;

        $this->postJson("/api/notifications/{$id}/lu")
            ->assertNotFound();
    }

}
