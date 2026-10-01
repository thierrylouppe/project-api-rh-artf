<?php

namespace App\Jobs;

use App\Services\ContratService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Alerte RH : dossiers intégrés sans CDI/CDD plus de 30 jours ouvrables après la PDS (art. 52).
 */
class ContratDelai30JoursJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ContratService $contratService): void
    {
        $contratService->notifierAlertesDelai30Jours();
    }
}
