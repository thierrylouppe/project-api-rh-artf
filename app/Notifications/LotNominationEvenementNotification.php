<?php

namespace App\Notifications;

use App\Models\LotNomination;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LotNominationEvenementNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly LotNomination $lot,
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
            'domaine'  => 'lot_nomination',
            'action'   => $this->action,
            'lot_id'   => $this->lot->id,
            'statut'   => $this->lot->statut?->value ?? $this->lot->statut,
            'message'  => $this->message(),
        ];
    }

    private function message(): string
    {
        return match ($this->action) {
            'creee'     => 'Un lot de nominations a été créé. Un seul circuit de validation est ouvert.',
            'approuvee' => 'Le lot de nominations a été approuvé.',
            'activee'   => 'Le lot de nominations a été activé. Toutes les lignes sont actives.',
            'rejetee'   => 'Le lot de nominations a été rejeté.',
            default     => 'Mise à jour d\'un lot de nominations.',
        };
    }
}
