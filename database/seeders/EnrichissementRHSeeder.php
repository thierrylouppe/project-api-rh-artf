<?php

namespace Database\Seeders;

use App\Enums\StatutEvaluation;
use App\Enums\StatutSessionEvaluation;
use App\Models\Agent;
use App\Models\Evaluation;
use App\Models\NoteEvaluation;
use App\Models\QuestionEvaluation;
use App\Models\SessionEvaluation;
use App\Models\StructureSanitaire;
use App\Models\TypeSanction;
use App\Models\User;
use App\Models\VisiteMedicale;
use App\Enums\TypeVisiteMedicale;
use App\Models\Avertissement;
use App\Models\Sanction;
use App\Enums\StatutSanction;
use App\Models\DocumentAgent;
use App\Models\TypeDocument;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * EnrichissementRHSeeder
 *
 * Groupe 6 + 7 — Enrichissement RH :
 *   • StructureSanitaire          (Centre médical ARTF)
 *   • VisiteMedicale              (embauche + annuelle pour chaque agent)
 *   • SessionEvaluation           (N-1 clôturée + N ouverte)
 *   • Evaluation + NoteEvaluation (fiches finalisées pour N-1)
 *   • DocumentAgent               (CIN + diplôme + photo)
 *   • Avertissement               (1-2 agents légers)
 *   • Sanction                    (1 blâme prononcé)
 */
class EnrichissementRHSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::where('email', 'admin@artf.cg')->first();
        $drhUser   = User::where('email', 'lydiane.gambou@artf.cg')->first();

        if (! $adminUser) {
            $this->command?->warn('Utilisateur admin introuvable — seeder ignoré.');
            return;
        }

        $adminId = $adminUser->id;
        $drhId   = $drhUser?->id ?? $adminId;

        $this->creerStructureSanitaire();
        $this->creerVisitesMedicales($adminId);
        $this->creerEvaluations($adminId, $drhId);
        $this->creerDocuments($adminId);
        $this->creerSanctions($adminId, $drhId);
    }

    // ── 1. Structure sanitaire ───────────────────────────────────────────
    private function creerStructureSanitaire(): void
    {
        StructureSanitaire::firstOrCreate(
            ['nom' => 'Centre Médical ARTF'],
            ['type' => 'formation_sanitaire', 'ville' => 'Brazzaville', 'telephone' => '+242 06 999 88 77', 'actif' => true]
        );
        StructureSanitaire::firstOrCreate(
            ['nom' => 'CHU de Brazzaville'],
            ['type' => 'formation_sanitaire', 'ville' => 'Brazzaville', 'telephone' => '+242 06 100 00 01', 'actif' => true]
        );
        StructureSanitaire::firstOrCreate(
            ['nom' => 'Clinique du Plateau'],
            ['type' => 'formation_sanitaire', 'ville' => 'Brazzaville', 'telephone' => '+242 06 200 00 02', 'actif' => true]
        );
    }

    // ── 2. Visites médicales ─────────────────────────────────────────────
    private function creerVisitesMedicales(int $adminId): void
    {
        $structure = StructureSanitaire::where('nom', 'Centre Médical ARTF')->first();
        if (! $structure) return;

        $agents = Agent::whereHas('contratActif')->get();
        $count  = 0;

        foreach ($agents as $agent) {
            // Visite d'embauche
            if (! VisiteMedicale::where('agent_id', $agent->id)
                ->where('type', TypeVisiteMedicale::EMBAUCHE)->exists()
            ) {
                VisiteMedicale::create([
                    'agent_id'             => $agent->id,
                    'type'                 => TypeVisiteMedicale::EMBAUCHE,
                    'date_visite'          => Carbon::parse($agent->date_prise_service)->addDays(7)->toDateString(),
                    'structure_sanitaire_id' => $structure->id,
                    'observations'         => 'Apte au travail — aucune contre-indication.',
                    'created_by'           => $adminId,
                ]);
                $count++;
            }

            // Visite annuelle — pour les agents actifs depuis plus d'un an
            $anciennete = Carbon::parse($agent->date_prise_service)->diffInYears(now());
            if ($anciennete >= 1) {
                $dateAnnuelle = now()->subMonths(3)->startOfMonth()->toDateString();
                if (! VisiteMedicale::where('agent_id', $agent->id)
                    ->where('type', TypeVisiteMedicale::ANNUELLE)
                    ->where('date_visite', $dateAnnuelle)->exists()
                ) {
                    VisiteMedicale::create([
                        'agent_id'               => $agent->id,
                        'type'                   => TypeVisiteMedicale::ANNUELLE,
                        'date_visite'            => $dateAnnuelle,
                        'structure_sanitaire_id' => $structure->id,
                        'observations'           => 'Visite annuelle — aptitude confirmée.',
                        'created_by'             => $adminId,
                    ]);
                    $count++;
                }
            }
        }

        $this->command?->info("Visites médicales créées : {$count}.");
    }

    // ── 3. Évaluations ──────────────────────────────────────────────────
    private function creerEvaluations(int $adminId, int $drhId): void
    {
        $anneeNm1 = now()->year - 1;
        $anneeN   = now()->year;

        $questions = QuestionEvaluation::where('actif', true)->orderBy('ordre')->get();
        if ($questions->isEmpty()) {
            $this->command?->warn('Aucune question d\'évaluation active — évaluations ignorées.');
            return;
        }

        // ── Session N-1 : clôturée ─────────────────────────────────────
        if (! SessionEvaluation::where('debut_session', "{$anneeNm1}-01-01")->exists()) {
            $sessionNm1 = SessionEvaluation::create([
                'debut_session' => "{$anneeNm1}-01-01",
                'fin_session'   => "{$anneeNm1}-12-31",
                'statut'        => StatutSessionEvaluation::CLOTUREE,
                'type_annee'    => ($anneeNm1 % 2 === 0) ? 'paire' : 'impaire',
                'semestre'      => null,
                'description'   => "Campagne d'évaluation annuelle {$anneeNm1}",
                'created_by'    => $adminId,
                'cloturee_par'  => $drhId,
                'cloturee_at'   => Carbon::create($anneeNm1, 12, 31, 17, 0),
            ]);

            // Évaluation finalisée pour tous les agents permanents
            $agents = Agent::where('statut', 'actif')
                ->whereHas('contratActif.typeContrat', fn ($q) => $q->whereIn('sigle', ['CDI', 'CDD']))
                ->with(['nominationActive', 'affectationActive'])
                ->get();

            $evalCount = 0;
            foreach ($agents as $agent) {
                // Supérieur : selon nomination, le directeur est noté par admin
                $superieurId = $this->trouverSuperieur($agent);

                // Notes semi-aléatoires mais cohérentes (basées sur l'ID)
                $seed     = $agent->id % 10;
                $noteMoy  = 14 + ($seed * 0.5); // entre 14 et 19 sur 20

                $affNot = $agent->affectationActive;

                $eval = Evaluation::create([
                    'session_id'               => $sessionNm1->id,
                    'agent_id'                 => $agent->id,
                    'superieur_id'             => $superieurId,
                    'affectation_notation_id'  => $affNot?->id,
                    'date_evaluation'          => Carbon::create($anneeNm1, 11, 15)->toDateString(),
                    'jours_absence_non_justifiee' => max(0, ($agent->id % 3) - 1), // 0, 0, ou 1 jour
                    'sanctions'                => false,
                    'avis_superieur'           => $noteMoy >= 17 ? 'Excellent travail, agent très impliqué.' : 'Bon travail, des progrès sont attendus.',
                    'note_globale'             => $noteMoy,
                    'statut'                   => StatutEvaluation::FINALISEE,
                    'signe_par_evaluateur_at'  => Carbon::create($anneeNm1, 11, 20),
                    'signe_par_evalue_at'      => Carbon::create($anneeNm1, 11, 25),
                    'date_validation_rh'       => Carbon::create($anneeNm1, 12, 10),
                    'validateur_rh_id'         => $drhId,
                    'commentaire_rh'           => 'Fiche conforme — validée RH.',
                    'conforme_rh'              => true,
                    'inscrit_tableau'          => $noteMoy >= 16,
                    'created_by'               => $adminId,
                ]);

                // Notes par critère
                foreach ($questions as $q) {
                    $noteMax = (float) $q->bareme_max;
                    $note    = round(min($noteMax, $noteMax * ($noteMoy / 20.0)), 2);
                    NoteEvaluation::create([
                        'evaluation_id' => $eval->id,
                        'question_id'   => $q->id,
                        'note_obtenue'  => $note,
                        'commentaire'   => null,
                    ]);
                }
                $evalCount++;
            }
            $this->command?->info("Évaluations {$anneeNm1} créées : {$evalCount}.");
        }

        // ── Session N : ouverte ────────────────────────────────────────
        if (! SessionEvaluation::where('debut_session', "{$anneeN}-01-01")->exists()) {
            SessionEvaluation::create([
                'debut_session' => "{$anneeN}-01-01",
                'fin_session'   => "{$anneeN}-12-31",
                'statut'        => StatutSessionEvaluation::OUVERTE,
                'type_annee'    => ($anneeN % 2 === 0) ? 'paire' : 'impaire',
                'description'   => "Campagne d'évaluation annuelle {$anneeN}",
                'created_by'    => $adminId,
            ]);
            $this->command?->info("Session d'évaluation {$anneeN} ouverte créée.");
        }
    }

    private function trouverSuperieur(Agent $agent): ?int
    {
        // Le supérieur est l'agent qui a une nomination active sur la même structure
        $nomination = $agent->nominationActive;
        if (! $nomination) return null;

        $structure = $nomination->structurable;
        if (! $structure) return null;

        // Chercher un agent avec nomination sur la structure parente ou même niveau
        return null; // Simplifié : null signifie auto-évaluation (admin valide)
    }

    // ── 4. Documents agents ──────────────────────────────────────────────
    private function creerDocuments(int $adminId): void
    {
        $typeCIN     = TypeDocument::where('nom', 'Acte de naissance')->first();
        $typeDiplome = TypeDocument::where('nom', 'Diplôme')->first();
        $typeCV      = TypeDocument::where('nom', 'Curriculum vitae')->first();

        $agents = Agent::whereHas('contratActif')->get();
        $count  = 0;

        foreach ($agents as $agent) {
            $slug = strtolower(str_replace(' ', '_', "{$agent->prenom}_{$agent->nom}"));

            // CIN / Acte de naissance
            if ($typeCIN && ! DocumentAgent::where('agent_id', $agent->id)
                ->where('type_document_id', $typeCIN->id)->exists()
            ) {
                DocumentAgent::create([
                    'agent_id'         => $agent->id,
                    'type_document_id' => $typeCIN->id,
                    'titre'            => "Acte de naissance — {$agent->prenom} {$agent->nom}",
                    'sous_dossier'     => 'identite',
                    'chemin_fichier'   => "/storage/agents/{$agent->matricule}/identite/acte_naissance_{$slug}.pdf",
                    'nom_original'     => "acte_naissance_{$slug}.pdf",
                    'taille'           => 256000 + ($agent->id * 1024),
                    'mime_type'        => 'application/pdf',
                ]);
                $count++;
            }

            // Diplôme
            if ($typeDiplome && ! DocumentAgent::where('agent_id', $agent->id)
                ->where('type_document_id', $typeDiplome->id)->exists()
            ) {
                DocumentAgent::create([
                    'agent_id'         => $agent->id,
                    'type_document_id' => $typeDiplome->id,
                    'titre'            => "Diplôme — {$agent->prenom} {$agent->nom}",
                    'sous_dossier'     => 'diplomes',
                    'chemin_fichier'   => "/storage/agents/{$agent->matricule}/diplomes/diplome_{$slug}.pdf",
                    'nom_original'     => "diplome_{$slug}.pdf",
                    'taille'           => 512000 + ($agent->id * 2048),
                    'mime_type'        => 'application/pdf',
                ]);
                $count++;
            }

            // CV
            if ($typeCV && ! DocumentAgent::where('agent_id', $agent->id)
                ->where('type_document_id', $typeCV->id)->exists()
            ) {
                DocumentAgent::create([
                    'agent_id'         => $agent->id,
                    'type_document_id' => $typeCV->id,
                    'titre'            => "CV — {$agent->prenom} {$agent->nom}",
                    'sous_dossier'     => 'cv',
                    'chemin_fichier'   => "/storage/agents/{$agent->matricule}/cv/cv_{$slug}.pdf",
                    'nom_original'     => "cv_{$slug}.pdf",
                    'taille'           => 128000 + ($agent->id * 512),
                    'mime_type'        => 'application/pdf',
                ]);
                $count++;
            }
        }

        $this->command?->info("Documents agents créés : {$count}.");
    }

    // ── 5. Sanctions et avertissements ──────────────────────────────────
    private function creerSanctions(int $adminId, int $drhId): void
    {
        $tsAvert  = TypeSanction::where('code', 'avertissement_ecrit')->first();
        $tsBLame  = TypeSanction::where('code', 'blame_ecrit')->first();
        $tsMise   = TypeSanction::where('code', 'mise_a_pied')->first();

        $count = 0;

        // ── Avertissement — Agent ARFT-00011 (Rodrigue NKAYA) ────────────
        $agent1 = Agent::where('matricule', 'ARFT-00011')->first();
        if ($agent1 && $tsAvert) {
            $superviseur = Agent::where('matricule', 'ARFT-00009')->first(); // CB
            $supUser = $superviseur?->user;

            if (! Avertissement::where('agent_id', $agent1->id)->exists()) {
                Avertissement::create([
                    'agent_id'   => $agent1->id,
                    'motif'      => 'Retards répétés au poste de travail sans justification préalable.',
                    'date'       => now()->subMonths(5)->toDateString(),
                    'emetteur_id'=> $supUser?->id ?? $adminId,
                ]);
                $count++;
            }

            if ($tsAvert && ! Sanction::where('agent_id', $agent1->id)
                ->where('type_sanction_id', $tsAvert->id)->exists()
            ) {
                Sanction::create([
                    'agent_id'         => $agent1->id,
                    'type_sanction_id' => $tsAvert->id,
                    'motif'            => 'Retards répétés (3 fois en 2 mois) malgré avertissement oral.',
                    'date_faits'       => now()->subMonths(4)->toDateString(),
                    'avec_indemnite'   => false,
                    'date_debut_effet' => now()->subMonths(4)->toDateString(),
                    'date_decision'    => now()->subMonths(4)->addDays(5)->toDateString(),
                    'statut'           => StatutSanction::VALIDEE,
                    'created_by'       => $supUser?->id ?? $adminId,
                    'validateur_id'    => $drhId,
                    'commentaire_validation' => 'Sanction prononcée après instruction.',
                    'conservee_jusqu_au' => now()->subMonths(4)->addYear()->toDateString(),
                ]);
                $count++;
            }
        }

        // ── Blâme écrit — Agent ARFT-00025 (Jocelyn MAFOUTA) ────────────
        $agent2 = Agent::where('matricule', 'ARFT-00025')->first();
        if ($agent2 && $tsBLame) {
            $cbAgent = Agent::where('matricule', 'ARFT-00024')->first();
            $cbUser  = $cbAgent?->user;

            if (! Sanction::where('agent_id', $agent2->id)
                ->where('type_sanction_id', $tsBLame->id)->exists()
            ) {
                Sanction::create([
                    'agent_id'         => $agent2->id,
                    'type_sanction_id' => $tsBLame->id,
                    'motif'            => 'Utilisation abusive des ressources informatiques à des fins personnelles.',
                    'date_faits'       => now()->subMonths(7)->toDateString(),
                    'avec_indemnite'   => false,
                    'date_debut_effet' => now()->subMonths(7)->toDateString(),
                    'date_decision'    => now()->subMonths(7)->addDays(7)->toDateString(),
                    'statut'           => StatutSanction::VALIDEE,
                    'created_by'       => $cbUser?->id ?? $adminId,
                    'validateur_id'    => $drhId,
                    'commentaire_validation' => 'Blâme prononcé — inscrit au dossier.',
                    'conservee_jusqu_au' => now()->subMonths(7)->addYears(2)->toDateString(),
                ]);
                $count++;
            }
        }

        // ── Mise à pied instruite (en attente) — ARFT-00035 ─────────────
        $agent3 = Agent::where('matricule', 'ARFT-00035')->first();
        if ($agent3 && $tsMise) {
            if (! Sanction::where('agent_id', $agent3->id)
                ->where('type_sanction_id', $tsMise->id)->exists()
            ) {
                Sanction::create([
                    'agent_id'         => $agent3->id,
                    'type_sanction_id' => $tsMise->id,
                    'motif'            => 'Absence injustifiée de 3 jours consécutifs.',
                    'date_faits'       => now()->subMonths(1)->toDateString(),
                    'nb_jours'         => 3,
                    'avec_indemnite'   => false,
                    'date_debut_effet' => null,
                    'date_decision'    => null,
                    'notes_instruction'=> 'Rapport transmis à la DRH. Audition prévue.',
                    'statut'           => StatutSanction::INSTRUITE,
                    'created_by'       => $adminId,
                ]);
                $count++;
            }
        }

        $this->command?->info("Sanctions/avertissements créés : {$count}.");
    }
}
