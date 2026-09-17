<?php

namespace Database\Seeders;

use App\Enums\StatutAbsence;
use App\Enums\StatutDemandeConge;
use App\Models\Absence;
use App\Models\Agent;
use App\Models\DemandeConge;
use App\Models\TypeAbsence;
use App\Models\TypeConge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * AbsenceCongeSeeder
 *
 * Pour chaque direction, crée un jeu réaliste de données :
 *   • 1 Permission d'absence  (CB → validée par son supérieur)
 *   • 1 Absence pour maladie  (Agent → validée par chef de bureau)
 *   • 1 Congé annuel          (CS → workflow complet N1 → RH → DG)
 *
 * Prérequis : AgentIntegrationSeeder déjà exécuté.
 */
class AbsenceCongeSeeder extends Seeder
{
    public function run(): void
    {
        // ── Chargement des référentiels ───────────────────────────────
        $taPermission = TypeAbsence::where('nom', "Permission d'absence")->first();
        $taMaladie    = TypeAbsence::where('nom', 'Absence pour maladie')->first();

        $tcAnnuel     = TypeConge::where('nom', 'Congé annuel')->first();
        $tcMaternite  = TypeConge::where('nom', 'Congé de maternité')->first();
        $tcMaladie    = TypeConge::where('nom', 'Congé maladie')->first();

        if (! $taPermission || ! $taMaladie || ! $tcAnnuel) {
            $this->command?->warn('Types absence/congé manquants — seeder ignoré.');
            return;
        }

        $adminUser = User::where('email', 'admin@artf.cg')->first();
        $drhUser   = User::where('email', 'lydiane.gambou@artf.cg')->first();
        $dgUser    = User::where('email', 'jean.pierre.moukala@artf.cg')->first();

        if (! $adminUser || ! $dgUser) {
            $this->command?->warn('Utilisateurs admin/DG introuvables — seeder ignoré.');
            return;
        }

        // DRH fallback → admin si Lydiane pas encore créée
        $drhUser = $drhUser ?? $adminUser;

        $absCreees   = 0;
        $congesCrees = 0;

        // ── Données par direction (matricules fixes du seeder agents) ─
        // Format : [directeur, chef_service, chef_bureau, [agent1, agent2], stagiaire]
        $directions = [
            // D.R.H.L
            ['dir' => 'ARFT-00017', 'cs' => 'ARFT-00018', 'cb' => 'ARFT-00019', 'agents' => ['ARFT-00020', 'ARFT-00021'], 'stg' => 'STG-0004'],
            // D.G
            ['dir' => 'ARFT-00002', 'cs' => 'ARFT-00003', 'cb' => 'ARFT-00004', 'agents' => ['ARFT-00005', 'ARFT-00006'], 'stg' => 'STG-0001'],
            // D.F
            ['dir' => 'ARFT-00007', 'cs' => 'ARFT-00008', 'cb' => 'ARFT-00009', 'agents' => ['ARFT-00010', 'ARFT-00011'], 'stg' => 'STG-0002'],
            // D.R
            ['dir' => 'ARFT-00012', 'cs' => 'ARFT-00013', 'cb' => 'ARFT-00014', 'agents' => ['ARFT-00015', 'ARFT-00016'], 'stg' => 'STG-0003'],
            // A.C
            ['dir' => 'ARFT-00022', 'cs' => 'ARFT-00023', 'cb' => 'ARFT-00024', 'agents' => ['ARFT-00025', 'ARFT-00026'], 'stg' => 'STG-0005'],
            // D.I.S.E
            ['dir' => 'ARFT-00027', 'cs' => 'ARFT-00028', 'cb' => 'ARFT-00029', 'agents' => ['ARFT-00030', 'ARFT-00031'], 'stg' => 'STG-0006'],
            // D.A.J.I.C
            ['dir' => 'ARFT-00032', 'cs' => 'ARFT-00033', 'cb' => 'ARFT-00034', 'agents' => ['ARFT-00035', 'ARFT-00036'], 'stg' => 'STG-0007'],
            // D.D.P.N
            ['dir' => 'ARFT-00037', 'cs' => 'ARFT-00038', 'cb' => 'ARFT-00039', 'agents' => ['ARFT-00040', 'ARFT-00041'], 'stg' => 'STG-0008'],
            // D.D.O
            ['dir' => 'ARFT-00042', 'cs' => 'ARFT-00043', 'cb' => 'ARFT-00044', 'agents' => ['ARFT-00045', 'ARFT-00046'], 'stg' => 'STG-0009'],
            // D.D.D
            ['dir' => 'ARFT-00047', 'cs' => 'ARFT-00048', 'cb' => 'ARFT-00049', 'agents' => ['ARFT-00050', 'ARFT-00051'], 'stg' => 'STG-0010'],
        ];

        // Base de dates (décalées par direction pour varier les données)
        $baseDate = Carbon::now()->subMonths(6);

        foreach ($directions as $idx => $d) {
            $offset = $idx * 12; // décalage en jours pour diversifier les dates

            $csAgent  = Agent::where('matricule', $d['cs'])->first();
            $cbAgent  = Agent::where('matricule', $d['cb'])->first();
            $agent1   = Agent::where('matricule', $d['agents'][0])->first();
            $agent2   = Agent::where('matricule', $d['agents'][1])->first();
            $dirAgent = Agent::where('matricule', $d['dir'])->first();

            $csUser  = $csAgent?->user;
            $cbUser  = $cbAgent?->user;
            $dirUser = $dirAgent?->user;

            if (! $csAgent || ! $cbAgent || ! $agent1) {
                continue;
            }

            // ── 1. PERMISSION D'ABSENCE — Chef de Bureau ─────────────
            // Durée : 1 jour | Validée par le chef de service
            $datePermission = $baseDate->copy()->addDays($offset + 2);
            if (! Absence::where('agent_id', $cbAgent->id)->where('type_absence_id', $taPermission->id)->exists()) {
                Absence::create([
                    'agent_id'              => $cbAgent->id,
                    'type_absence_id'       => $taPermission->id,
                    'date_debut'            => $datePermission->toDateString(),
                    'date_fin'              => $datePermission->toDateString(),
                    'nb_jours'              => 1,
                    'justifiee'             => true,
                    'motif'                 => 'Démarche administrative personnelle',
                    'statut'                => StatutAbsence::VALIDEE,
                    'created_by'            => $cbUser?->id ?? $adminUser->id,
                    'valideur_id'           => $csUser?->id ?? $adminUser->id,
                    'commentaire_validation'=> 'Approuvée — absence justifiée',
                ]);
                $absCreees++;
            }

            // ── 2. ABSENCE POUR MALADIE — Agent 1 ────────────────────
            // Durée : 3 jours | Validée par le chef de bureau
            $dateMaladie = $baseDate->copy()->addDays($offset + 15);
            if (! Absence::where('agent_id', $agent1->id)->where('type_absence_id', $taMaladie->id)->exists()) {
                Absence::create([
                    'agent_id'              => $agent1->id,
                    'type_absence_id'       => $taMaladie->id,
                    'date_debut'            => $dateMaladie->toDateString(),
                    'date_fin'              => $dateMaladie->copy()->addDays(2)->toDateString(),
                    'nb_jours'              => 3,
                    'justifiee'             => true,
                    'motif'                 => 'Certificat médical — grippe saisonnière',
                    'statut'                => StatutAbsence::VALIDEE,
                    'created_by'            => $agent1->user?->id ?? $adminUser->id,
                    'valideur_id'           => $cbUser?->id ?? $adminUser->id,
                    'commentaire_validation'=> 'Certificat médical vérifié',
                ]);
                $absCreees++;
            }

            // ── 3. ABSENCE EN ATTENTE — Agent 2 ──────────────────────
            // Absence non encore validée (pour tester l'état en_attente)
            $dateEnAttente = Carbon::now()->addDays(5);
            if (! Absence::where('agent_id', $agent2->id)->where('type_absence_id', $taPermission->id)->exists()) {
                Absence::create([
                    'agent_id'              => $agent2->id,
                    'type_absence_id'       => $taPermission->id,
                    'date_debut'            => $dateEnAttente->toDateString(),
                    'date_fin'              => $dateEnAttente->copy()->addDay()->toDateString(),
                    'nb_jours'              => 2,
                    'justifiee'             => false,
                    'motif'                 => 'Rendez-vous médical',
                    'statut'                => StatutAbsence::EN_ATTENTE,
                    'created_by'            => $agent2->user?->id ?? $adminUser->id,
                    'valideur_id'           => null,
                    'commentaire_validation'=> null,
                ]);
                $absCreees++;
            }

            // ── 4. CONGÉ ANNUEL — Chef de Service (workflow complet) ──
            // SOUMISE → VALIDEE_N1 → VALIDEE_RH → VALIDEE_DG
            $dateConge     = $baseDate->copy()->addDays($offset + 30);
            $dateCongeDebut = $dateConge->toDateString();
            $dateCongeFin   = $dateConge->copy()->addDays(18)->toDateString(); // 18 jours ouvrés

            if (! DemandeConge::where('agent_id', $csAgent->id)->where('type_conge_id', $tcAnnuel->id)->exists()) {
                DemandeConge::create([
                    'agent_id'           => $csAgent->id,
                    'type_conge_id'      => $tcAnnuel->id,
                    'date_debut'         => $dateCongeDebut,
                    'date_fin'           => $dateCongeFin,
                    'nb_jours'           => 18,
                    'motif'              => 'Congé annuel — exercice en cours',
                    'justificatif_path'  => null,
                    'statut'             => StatutDemandeConge::VALIDEE_DG,
                    'created_by'         => $csUser?->id ?? $adminUser->id,
                    // N+1 = Directeur de la direction
                    'valideur_n1_id'     => $dirUser?->id ?? $adminUser->id,
                    'commentaire_n1'     => 'Approuvé — planification vérifiée',
                    'date_validation_n1' => $dateConge->copy()->subDays(14),
                    // RH = DRHL
                    'valideur_rh_id'     => $drhUser->id,
                    'commentaire_rh'     => 'Solde congé suffisant',
                    'date_validation_rh' => $dateConge->copy()->subDays(10),
                    // DG
                    'valideur_dg_id'     => $dgUser->id,
                    'commentaire_dg'     => 'Accordé',
                    'date_validation_dg' => $dateConge->copy()->subDays(7),
                ]);
                $congesCrees++;
            }

            // ── 5. CONGÉ EN ATTENTE — Chef de Bureau ─────────────────
            // Demande soumise, en attente de validation N+1
            $dateConge2 = Carbon::now()->addDays(30 + $offset);
            if (! DemandeConge::where('agent_id', $cbAgent->id)->where('type_conge_id', $tcAnnuel->id)->exists()) {
                DemandeConge::create([
                    'agent_id'      => $cbAgent->id,
                    'type_conge_id' => $tcAnnuel->id,
                    'date_debut'    => $dateConge2->toDateString(),
                    'date_fin'      => $dateConge2->copy()->addDays(13)->toDateString(),
                    'nb_jours'      => 14,
                    'motif'         => 'Congé annuel — deuxième semestre',
                    'statut'        => StatutDemandeConge::SOUMISE,
                    'created_by'    => $cbUser?->id ?? $adminUser->id,
                ]);
                $congesCrees++;
            }

            // ── 6. CONGÉ ANNUEL DIRECTEUR — Validé directement RH+DG ─
            if ($dirAgent && $dirUser) {
                $dateConge3 = $baseDate->copy()->addDays($offset + 45);
                if (! DemandeConge::where('agent_id', $dirAgent->id)->where('type_conge_id', $tcAnnuel->id)->exists()) {
                    DemandeConge::create([
                        'agent_id'           => $dirAgent->id,
                        'type_conge_id'      => $tcAnnuel->id,
                        'date_debut'         => $dateConge3->toDateString(),
                        'date_fin'           => $dateConge3->copy()->addDays(21)->toDateString(),
                        'nb_jours'           => 22,
                        'motif'              => 'Congé annuel — directeur',
                        'statut'             => StatutDemandeConge::VALIDEE_DG,
                        'created_by'         => $dirUser->id,
                        'valideur_n1_id'     => $drhUser->id,
                        'commentaire_n1'     => 'Approuvé DRH',
                        'date_validation_n1' => $dateConge3->copy()->subDays(12),
                        'valideur_rh_id'     => $drhUser->id,
                        'commentaire_rh'     => 'OK RH',
                        'date_validation_rh' => $dateConge3->copy()->subDays(8),
                        'valideur_dg_id'     => $dgUser->id,
                        'commentaire_dg'     => 'Accordé',
                        'date_validation_dg' => $dateConge3->copy()->subDays(5),
                    ]);
                    $congesCrees++;
                }
            }
        }

        $this->command?->info("Absences créées : {$absCreees} — Congés créés : {$congesCrees}.");
    }
}
