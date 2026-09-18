<?php

namespace Database\Seeders;

use App\Enums\StatutPaieLot;
use App\Models\Agent;
use App\Models\PaieLot;
use App\Models\PaieLotLigne;
use App\Models\PaieLotLigneDetail;
use App\Models\PaieElement;
use App\Models\PaieElementAffectation;
use App\Models\SalaireAgent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * PaieLotSeeder
 *
 * Crée 4 lots de paie avec des statuts progressifs :
 *   M-3 : CLOTURE  (historique archivé)
 *   M-2 : VALIDE   (validé, en attente clôture)
 *   M-1 : CONTROLE (en cours de contrôle)
 *   M   : GENERE   (mois en cours, généré)
 *
 * Chaque lot contient une ligne par agent CDI/CDD
 * avec les détails salaire de base + primes/indemnités.
 */
class PaieLotSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@artf.cg')->first();
        $drhUser   = User::where('email', 'lydiane.gambou@artf.cg')->first();
        $dgUser    = User::where('email', 'jean.pierre.moukala@artf.cg')->first();

        if (! $adminUser) {
            $this->command?->warn('Utilisateur admin introuvable — seeder ignoré.');
            return;
        }

        $rhId    = $drhUser?->id ?? $adminUser->id;
        $dgId    = $dgUser?->id ?? $adminUser->id;
        $adminId = $adminUser->id;

        // Mois à générer : M-3 → M actuel
        $moisAGenerer = [
            ['offset' => -3, 'statut' => StatutPaieLot::CLOTURE],
            ['offset' => -2, 'statut' => StatutPaieLot::VALIDE],
            ['offset' => -1, 'statut' => StatutPaieLot::CONTROLE],
            ['offset' =>  0, 'statut' => StatutPaieLot::GENERE],
        ];

        // Agents éligibles (CDI/CDD actifs avec salaire actif)
        $agents = Agent::whereHas('contratActif.typeContrat', fn ($q) => $q->whereIn('sigle', ['CDI', 'CDD']))
            ->whereHas('salaireActuel')
            ->with(['salaireActuel', 'fonction', 'grade', 'categorie'])
            ->get();

        // Éléments de paie indexés par code
        $elements = PaieElement::whereIn('code', [
            'salaire_fonctionnel',
            'indemnite_transport',
            'prime_representation',
            'prime_logement',
            'prime_caisse',
            'prime_risque',
            'retenue_cnss',
        ])->get()->keyBy('code');

        // Affectations d'éléments par agent
        $affectations = PaieElementAffectation::whereNull('date_fin')->get()->groupBy('agent_id');

        $lotsCreated = 0;

        foreach ($moisAGenerer as $config) {
            $date   = now()->startOfMonth()->addMonths($config['offset']);
            $annee  = (int) $date->year;
            $mois   = (int) $date->month;
            $statut = $config['statut'];

            // Éviter les doublons
            if (PaieLot::where('annee', $annee)->where('mois', $mois)->exists()) {
                continue;
            }

            // Dates de workflow selon statut
            $genAt     = $date->copy()->addDays(1);
            $ctrlAt    = $statut->value !== StatutPaieLot::GENERE->value ? $date->copy()->addDays(3) : null;
            $valAt     = in_array($statut->value, [StatutPaieLot::VALIDE->value, StatutPaieLot::CLOTURE->value], true)
                            ? $date->copy()->addDays(5) : null;
            $cloAt     = $statut === StatutPaieLot::CLOTURE ? $date->copy()->addDays(8) : null;

            // ── Calcul des totaux ────────────────────────────────────────
            $totalGains = 0; $totalRetenues = 0; $nbLignes = 0;
            $lignesData = [];

            foreach ($agents as $agent) {
                $sal = $agent->salaireActuel;
                if (! $sal) continue;

                $montantBase = (float) $sal->montant_net;
                $gains       = $montantBase;
                $retenues    = 0;

                $details = [];

                // Salaire de base
                $details[] = [
                    'code'    => 'salaire_base_indiciaire',
                    'libelle' => 'Salaire de base indiciaire',
                    'nature'  => 'prime',       // nature la plus proche (salaire = prime dans cet enum)
                    'sens'    => 'gain',
                    'montant' => $montantBase,
                    'source'  => 'base',
                ];

                // Primes et indemnités affectées
                $agentAffect = $affectations->get($agent->id, collect());
                foreach ($agentAffect as $aff) {
                    $elmt = $elements->firstWhere('id', $aff->paie_element_id);
                    if (! $elmt) continue;
                    $montant = (float) $aff->montant;
                    $gains  += $montant;
                    $details[] = [
                        'paie_element_id' => $elmt->id,
                        'code'    => $elmt->code,
                        'libelle' => $elmt->libelle,
                        'nature'  => $elmt->nature, // déjà le bon slug
                        'sens'    => 'gain',
                        'montant' => $montant,
                        'source'  => 'affectation',
                    ];
                }

                // Retenue CNSS : 8% du salaire de base
                $retCNSS = round($montantBase * 0.08, 2);
                $retenues += $retCNSS;
                if ($elCNSS = $elements->get('retenue_cnss')) {
                    $details[] = [
                        'paie_element_id' => $elCNSS->id,
                        'code'    => 'retenue_cnss',
                        'libelle' => 'Cotisation CNSS (8%)',
                        'nature'  => 'retenue',
                        'sens'    => 'retenue',
                        'montant' => $retCNSS,
                        'source'  => 'calcul_auto',
                    ];
                }

                $montantNet = $gains - $retenues;
                $totalGains    += $gains;
                $totalRetenues += $retenues;
                $nbLignes++;

                $lignesData[] = [
                    'agent'       => $agent,
                    'sal'         => $sal,
                    'base'        => $montantBase,
                    'gains'       => $gains,
                    'retenues'    => $retenues,
                    'net'         => $montantNet,
                    'details'     => $details,
                ];
            }

            // ── Créer le lot ─────────────────────────────────────────────
            $lot = PaieLot::create([
                'annee'         => $annee,
                'mois'          => $mois,
                'statut'        => $statut,
                'generated_at'  => $genAt,
                'generated_by'  => $adminId,
                'controle_at'   => $ctrlAt,
                'controle_par'  => $ctrlAt ? $rhId : null,
                'valide_at'     => $valAt,
                'valide_par'    => $valAt ? $rhId : null,
                'cloture_at'    => $cloAt,
                'cloture_par'   => $cloAt ? $dgId : null,
                'total_gains'   => $totalGains,
                'total_retenues'=> $totalRetenues,
                'total_net'     => $totalGains - $totalRetenues,
                'nb_lignes'     => $nbLignes,
                'anomalies'     => null,
            ]);

            // ── Créer les lignes et détails ──────────────────────────────
            foreach ($lignesData as $ld) {
                $ligne = PaieLotLigne::create([
                    'lot_id'          => $lot->id,
                    'agent_id'        => $ld['agent']->id,
                    'salaire_agent_id'=> $ld['sal']->id,
                    'hors_grille'     => false,
                    'montant_base'    => $ld['base'],
                    'total_gains'     => $ld['gains'],
                    'total_retenues'  => $ld['retenues'],
                    'montant_net'     => $ld['net'],
                    'nb_anomalies'    => 0,
                    'snapshot_agent'  => [
                        'matricule' => $ld['agent']->matricule,
                        'nom'       => $ld['agent']->nom,
                        'prenom'    => $ld['agent']->prenom,
                        'grade'     => $ld['agent']->grade?->nom,
                        'categorie' => $ld['agent']->categorie?->nom,
                        'echelon'   => $ld['sal']->echelon,
                    ],
                ]);

                foreach ($ld['details'] as $det) {
                    PaieLotLigneDetail::create([
                        'ligne_id'        => $ligne->id,
                        'paie_element_id' => $det['paie_element_id'] ?? null,
                        'code'            => $det['code'],
                        'libelle'         => $det['libelle'],
                        'nature'          => $det['nature'],
                        'sens'            => $det['sens'],
                        'montant'         => $det['montant'],
                        'source'          => $det['source'],
                        'meta'            => null,
                    ]);
                }
            }

            $lotsCreated++;
            $moisLabel = $date->locale('fr')->translatedFormat('F Y');
            $this->command?->info("  Lot {$moisLabel} ({$statut->label()}) : {$nbLignes} bulletins — Net total : ".number_format($totalGains - $totalRetenues, 0, '.', ' ').' FCFA');
        }

        $this->command?->info("Lots de paie créés : {$lotsCreated}.");
    }
}
