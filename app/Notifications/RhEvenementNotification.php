<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RhEvenementNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        private readonly string $domaine,
        private readonly string $action,
        private readonly string $message,
        private readonly array $meta = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return array_merge($this->meta, [
            'domaine' => $this->domaine,
            'action'  => $this->action,
            'message' => $this->message,
        ]);
    }
}
