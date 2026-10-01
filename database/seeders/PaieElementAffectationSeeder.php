<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * PaieElementAffectationSeeder
 *
 * Affecte des primes/indemnités spécifiques par fonction :
 *   Tous          → Indemnité de transport (14 000 FCFA/mois)
 *   DG/DC/DD      → Prime de représentation (200 000 FCFA/mois)
 *   DG/DC/DD      → Prime de logement       (150 000 FCFA/mois)
 *   CS/CB         → Prime de caisse         (35 000 FCFA/mois) — pour DRHL uniquement
 *   Agents RH     → Prime de risque          (20 000 FCFA/mois)
 */
class PaieElementAffectationSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@artf.cg')->first();
        $adminId   = $adminUser?->id ?? 1;

        $peTransport  = PaieElement::where('code', 'indemnite_transport')->first();
        $peRepres     = PaieElement::where('code', 'prime_representation')->first();
        $peLogement   = PaieElement::where('code', 'prime_logement')->first();
        $peCaisse     = PaieElement::where('code', 'prime_caisse')->first();
        $peRisque     = PaieElement::where('code', 'prime_risque')->first();

        if (! $peTransport) {
            $this->command?->warn('Éléments de paie introuvables — seeder ignoré.');
            return;
        }

        $dateDebut = Carbon::now()->subYears(2)->startOfYear()->toDateString();
        $created = 0;

        $agents = Agent::with('fonction')->whereHas('contratActif')->get();

        foreach ($agents as $agent) {
            $sigle = $agent->fonction?->sigle ?? 'AGT';

            // ── Indemnité de transport — TOUS ────────────────────────────
            $this->creer($agent->id, $peTransport->id, 14000, $dateDebut, $adminId) && $created++;

            // ── Prime de représentation — DG, DC, DD ─────────────────────
            if ($peRepres && in_array($sigle, ['DG', 'DC', 'DD'])) {
                $montant = $sigle === 'DG' ? 350000 : 200000;
                $this->creer($agent->id, $peRepres->id, $montant, $dateDebut, $adminId) && $created++;
            }

            // ── Prime de logement — DG, DC, DD ───────────────────────────
            if ($peLogement && in_array($sigle, ['DG', 'DC', 'DD'])) {
                $montant = $sigle === 'DG' ? 250000 : 150000;
                $this->creer($agent->id, $peLogement->id, $montant, $dateDebut, $adminId) && $created++;
            }

            // ── Prime de caisse — CS/CB de la DRHL (bureau RH-Solde) ────
            if ($peCaisse && in_array($sigle, ['CS', 'CB'])) {
                // Seulement pour agents de la DRHL (matricules 17→21)
                $num = (int) substr($agent->matricule, -5);
                if ($num >= 17 && $num <= 21) {
                    $this->creer($agent->id, $peCaisse->id, 45000, $dateDebut, $adminId) && $created++;
                }
            }

            // ── Prime de risque — Agents (exposition aux risques) ────────
            if ($peRisque && $sigle === 'AGT') {
                $this->creer($agent->id, $peRisque->id, 20000, $dateDebut, $adminId) && $created++;
            }
        }

        $this->command?->info("Affectations éléments de paie créées : {$created}.");
    }

    private function creer(
        int $agentId, int $elementId, float $montant, string $dateDebut, int $adminId,
    ): bool {
        if (PaieElementAffectation::where('agent_id', $agentId)
            ->where('paie_element_id', $elementId)
            ->whereNull('date_fin')
            ->exists()) {
            return false;
        }
        PaieElementAffectation::create([
            'agent_id'        => $agentId,
            'paie_element_id' => $elementId,
            'montant'         => $montant,
            'date_debut'      => $dateDebut,
            'date_fin'        => null,
            'motif'           => 'Affectation initiale — données de test',
            'created_by'      => $adminId,
        ]);
        return true;
    }
}
