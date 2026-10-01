<?php

namespace App\Jobs;

use App\Services\PositionConventionnelleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte J-7 et J-0 avant la fin d'une position conventionnelle active (art. 78–80).
 */
class PositionConventionnelleEcheanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SEUILS_JOURS = [7, 0];

    public function handle(PositionConventionnelleService $service): void
    {
        foreach (self::SEUILS_JOURS as $jours) {
            $service->notifierEcheances($jours);
        }
    }
}
