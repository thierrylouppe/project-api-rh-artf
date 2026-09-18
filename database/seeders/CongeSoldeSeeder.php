<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\CongeSolde;
use App\Models\TypeConge;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * CongeSoldeSeeder
 *
 * Crée les soldes de congé pour chaque agent CDI actif.
 * Règle CCN art. 47 : 2,5 jours ouvrables par mois travaillé → 30 j/an
 * Bonification ancienneté (art. 48) :
 *   ≥ 5 ans  → +1 jour
 *   ≥ 10 ans → +2 jours
 *   ≥ 15 ans → +3 jours
 *   ≥ 20 ans → +4 jours
 */
class CongeSoldeSeeder extends Seeder
{
    public function run(): void
    {
        $tcAnnuel = TypeConge::where('nom', 'Congé annuel')->first();
        if (! $tcAnnuel) {
            $this->command?->warn('Type congé annuel introuvable.');
            return;
        }

        $anneeN    = (int) now()->year;
        $anneeNm1  = $anneeN - 1;
        $created   = 0;

        // Agents CDI actifs uniquement
        $agents = Agent::where('statut', 'actif')
            ->whereHas('contratActif.typeContrat', fn ($q) => $q->whereIn('sigle', ['CDI', 'CDD']))
            ->get();

        foreach ($agents as $agent) {
            $anciennete = (int) Carbon::parse($agent->date_prise_service)->diffInYears(now());

            // Calcul jours bonification ancienneté (art. 48)
            $bonif = match(true) {
                $anciennete >= 20 => 4,
                $anciennete >= 15 => 3,
                $anciennete >= 10 => 2,
                $anciennete >= 5  => 1,
                default           => 0,
            };

            $joursBase = 30;
            $joursTotal = $joursBase + $bonif;

            // ── Année N-1 — solde clôturé (quelques jours restants) ──────
            if (! CongeSolde::where('agent_id', $agent->id)
                ->where('type_conge_id', $tcAnnuel->id)
                ->where('annee', $anneeNm1)
                ->exists()
            ) {
                // Simuler qu'il reste 3 à 8 jours non utilisés (réaliste)
                $resteNm1 = ($agent->id % 6) + 3; // 3 à 8 jours selon ID
                CongeSolde::create([
                    'agent_id'         => $agent->id,
                    'type_conge_id'    => $tcAnnuel->id,
                    'annee'            => $anneeNm1,
                    'solde_initial'    => $joursTotal,
                    'solde_actuel'     => $resteNm1,
                    'jours_anciennete' => $bonif,
                ]);
                $created++;
            }

            // ── Année N — solde en cours ──────────────────────────────────
            // Acquisition prorata : mois écoulés × 2.5
            $moisEcoules = now()->month;
            $acquis = min($joursTotal, round($moisEcoules * 2.5, 1));
            // Déduire le congé annuel validé créé par AbsenceCongeSeeder (18 jours CS, 22 jours Dir)
            $dejaUtilises = match ($agent->fonction?->sigle) {
                'CS' => 18,
                'DC', 'DD', 'DC' => 22,
                default => 0,
            };
            $soldeActuel = max(0, $acquis - $dejaUtilises);

            if (! CongeSolde::where('agent_id', $agent->id)
                ->where('type_conge_id', $tcAnnuel->id)
                ->where('annee', $anneeN)
                ->exists()
            ) {
                CongeSolde::create([
                    'agent_id'         => $agent->id,
                    'type_conge_id'    => $tcAnnuel->id,
                    'annee'            => $anneeN,
                    'solde_initial'    => $joursTotal,
                    'solde_actuel'     => $soldeActuel,
                    'jours_anciennete' => $bonif,
                ]);
                $created++;
            }
        }

        $this->command?->info("Soldes congé créés : {$created} ({$agents->count()} agents × 2 ans).");
    }
}
