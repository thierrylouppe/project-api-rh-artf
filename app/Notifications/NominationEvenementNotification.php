<?php

namespace App\Notifications;

use App\Models\Nomination;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NominationEvenementNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Nomination $nomination,
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
            'domaine'       => 'nomination',
            'action'        => $this->action,
            'nomination_id' => $this->nomination->id,
            'agent_id'      => $this->nomination->agent_id,
            'poste'         => $this->nomination->poste,
            'statut'        => $this->nomination->statut?->value ?? $this->nomination->statut,
            'message'       => $this->message(),
        ];
    }

    private function message(): string
    {
        return match ($this->action) {
            'creee'     => 'Une nomination a été créée et le circuit de validation est ouvert.',
            'approuvee' => 'La nomination a été approuvée (circuit terminé).',
            'activee'   => 'La nomination a été activée.',
            'rejetee'   => 'La nomination a été rejetée.',
            default     => 'Mise à jour d\'une nomination.',
        };
    }
}
