<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Salaire;
use App\Services\SalaireAgentService;
use App\Services\SalaireService;
use Illuminate\Database\Seeder;

/**
 * Backfill : crée le salaire actif manquant pour les agents
 * ayant un contrat CDI/CDD actif. Idempotent (skip si salaire actuel).
 *
 * Prérequis : référentiels grille (classes, paramètres) seedés.
 * Usage : php artisan db:seed --class=SalaireAgentSeeder
 */
class SalaireAgentSeeder extends Seeder
{
    private const SIGLES_ELIGIBLES = ['CDI', 'CDD'];

    public function run(): void
    {
        $salaireService = app(SalaireService::class);
        $salaireAgentService = app(SalaireAgentService::class);

        if (Salaire::query()->doesntExist()) {
            $result = $salaireService->generateGrille();
            $this->command?->info("Grille générée ({$result['total']} lignes).");
        }

        $agents = Agent::query()
            ->whereHas('contratActif.typeContrat', function ($q) {
                $q->whereIn('sigle', self::SIGLES_ELIGIBLES);
            })
            ->whereDoesntHave('salaireActuel')
            ->with(['contratActif.typeContrat', 'echelon'])
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($agents as $agent) {
            try {
                $result = $salaireAgentService->creerSalaireInitial($agent, $agent->contratActif);
                if ($result !== null) {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $this->command?->warn("Agent #{$agent->id} : {$e->getMessage()}");
            }
        }

        $this->command?->info("Salaires agents créés : {$created} — ignorés / erreurs : {$skipped}.");
    }
}
