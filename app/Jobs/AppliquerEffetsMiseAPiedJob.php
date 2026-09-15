<?php

namespace App\Jobs;

use App\Services\SanctionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Applique les effets de mise à pied : suspend l'agent sur la période, le réactive à l'échéance.
 *
 * Planification : routes/console.php (tous les jours 08h00).
 */
class AppliquerEffetsMiseAPiedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(SanctionService $sanctionService): void
    {
        $sanctionService->appliquerEffetsMiseAPied();
    }
}
