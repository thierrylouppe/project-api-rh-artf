<?php

namespace App\Notifications;

use App\Models\Affectation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AffectationEvenementNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Affectation $affectation,
        private readonly string $action,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'domaine'        => 'affectation',
            'action'         => $this->action,
            'affectation_id' => $this->affectation->id,
            'agent_id'       => $this->affectation->agent_id,
            'statut'         => $this->affectation->statut?->value ?? $this->affectation->statut,
            'message'        => $this->message(),
        ];
    }

    private function message(): string
    {
        return match ($this->action) {
            'creee'     => 'Une affectation a été créée et le circuit de validation est ouvert.',
            'approuvee' => 'L\'affectation a été approuvée (circuit terminé).',
            'activee'   => 'L\'affectation a été activée.',
            'rejetee'   => 'L\'affectation a été rejetée.',
            default     => 'Mise à jour d\'une affectation.',
        };
    }
}
