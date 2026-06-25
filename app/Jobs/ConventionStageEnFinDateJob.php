<?php

namespace App\Jobs;

use App\Interfaces\ConventionStageInterface;
use App\Models\ConventionStage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job quotidien (08h00) — alerte J-15 avant la fin des stages en cours.
 *
 * Planification dans App\Console\Kernel (ou Schedule::job()) :
 *   $schedule->job(new ConventionStageEnFinDateJob)->dailyAt('08:00');
 */
class ConventionStageEnFinDateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SEUILS_JOURS = [15];

    public function handle(ConventionStageInterface $conventionRepository): void
    {
        foreach (self::SEUILS_JOURS as $jours) {
            $conventions = $conventionRepository->getProchesEcheance($jours);

            foreach ($conventions as $convention) {
                $this->notifierEcheance($convention, $jours);
            }
        }
    }

    private function notifierEcheance(ConventionStage $convention, int $jours): void
    {
        $agent   = $convention->agent;
        $tuteur  = $convention->tuteurInterne;

        Log::channel('daily')->info("Alerte stage J-{$jours}", [
            'convention_id' => $convention->id,
            'agent'         => $agent?->nom_complet,
            'etablissement' => $convention->etablissement,
            'date_fin'      => $convention->date_fin->format('d/m/Y'),
            'tuteur'        => $tuteur?->nom_complet ?? 'Non assigné',
        ]);

        // TODO : envoyer notification Mail/Notification Laravel au tuteur et aux RH
        // Notification::send([$agent, $tuteur], new StageProcheFin($convention, $jours));
    }
}
