<?php

namespace App\Jobs;

use App\Interfaces\ConventionStageInterface;
use App\Interfaces\UserInterface;
use App\Models\ConventionStage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job quotidien (08h00) — alerte J-15 avant la fin des stages en cours.
 *
 * Planification : routes/console.php
 */
class ConventionStageEnFinDateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SEUILS_JOURS = [15];

    public function handle(
        ConventionStageInterface $conventionRepository,
        NotificationService $notificationService,
        UserInterface $userRepository,
    ): void {
        foreach (self::SEUILS_JOURS as $jours) {
            $conventions = $conventionRepository->getProchesEcheance($jours);

            foreach ($conventions as $convention) {
                $this->notifierEcheance($convention, $jours, $notificationService, $userRepository);
            }
        }
    }

    private function notifierEcheance(
        ConventionStage $convention,
        int $jours,
        NotificationService $notificationService,
        UserInterface $userRepository,
    ): void {
        $agent  = $convention->agent;
        $tuteur = $convention->tuteurInterne;

        Log::channel('daily')->info("Alerte stage J-{$jours}", [
            'convention_id' => $convention->id,
            'agent'         => $agent?->nom_complet,
            'etablissement' => $convention->etablissement,
            'date_fin'      => $convention->date_fin?->format('d/m/Y'),
            'tuteur'        => $tuteur?->nom_complet ?? 'Non assigné',
        ]);

        $message = sprintf(
            'Le stage de %s arrive à échéance dans %d jour(s) (fin le %s).',
            $agent?->nom_complet ?? 'un stagiaire',
            $jours,
            $convention->date_fin?->format('d/m/Y') ?? '—'
        );

        $meta = [
            'convention_id' => $convention->id,
            'agent_id'      => $convention->agent_id,
            'jours'         => $jours,
            'date_fin'      => $convention->date_fin?->toDateString(),
        ];

        $destinataires = collect();

        if ($convention->agent_id) {
            $compteAgent = $userRepository->findByAgentId((int) $convention->agent_id);
            if ($compteAgent instanceof User) {
                $destinataires->push($compteAgent);
            }
        }

        if ($convention->tuteur_interne_id) {
            $compteTuteur = $userRepository->findByAgentId((int) $convention->tuteur_interne_id);
            if ($compteTuteur instanceof User) {
                $destinataires->push($compteTuteur);
            }
        }

        try {
            $destinataires = $destinataires->concat($userRepository->getByRole('rh'));
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            // Rôle absent (ex. tests sans seeder) : on notifie seulement agent / tuteur.
        }

        $notificationService->notifierEvenementGroupe(
            $destinataires,
            'stage',
            'echeance',
            $message,
            $meta
        );
    }
}
