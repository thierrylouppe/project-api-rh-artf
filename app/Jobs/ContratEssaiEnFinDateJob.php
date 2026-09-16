<?php

namespace App\Jobs;

use App\Services\ContratService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte J-7 et J-0 avant la fin de la période d'essai (art. 49).
 */
class ContratEssaiEnFinDateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SEUILS_JOURS = [7, 0];

    public function handle(ContratService $contratService): void
    {
        foreach (self::SEUILS_JOURS as $jours) {
            $contratService->notifierEssaisEcheance($jours);
        }
    }
}
