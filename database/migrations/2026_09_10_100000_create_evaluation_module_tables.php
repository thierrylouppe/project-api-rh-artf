<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Module Évaluation / Notation / Avancement
 *
 * Tables créées :
 *   - session_evaluations   : cycle annuel (une seule ouverte à la fois)
 *   - question_evaluations  : grille de 24 critères paramétrables
 *   - evaluations           : fiche par agent × session
 *   - note_evaluations      : note par fiche × question
 *
 * CCN ARTF art. 60–70.
 */
return new class extends Migration
{
    public function up(): void
    {
        // --------------------------------------------------------
        // 1. session_evaluations
        // --------------------------------------------------------
        Schema::create('session_evaluations', function (Blueprint $table) {
            $table->id();

            $table->date('debut_session');
            $table->date('fin_session')->nullable();

            // statut de la session (une seule session 'ouverte' tolérée — voir SessionEvaluationService)
            $table->enum('statut', ['ouverte', 'cloturee', 'annulee'])->default('ouverte');

            // D8 : parité + semestre optionnel (Phase 2+)
            $table->enum('type_annee', ['paire', 'impaire'])->nullable();
            $table->tinyInteger('semestre')->unsigned()->nullable()->comment('1 ou 2');

            $table->text('description')->nullable();

            // traçabilité
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cloturee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cloturee_at')->nullable();

            $table->timestamps();
        });

        // --------------------------------------------------------
        // 2. question_evaluations (grille RH-paramétrable)
        // --------------------------------------------------------
        Schema::create('question_evaluations', function (Blueprint $table) {
            $table->id();

            $table->string('libelle');
            $table->enum('type_critere', ['competence_pro', 'assiduite', 'relation_sociale']);

            // bareme individuel : la somme de toutes les questions actives doit = 20
            $table->decimal('bareme_max', 5, 2);

            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);

            $table->timestamps();
        });

        // --------------------------------------------------------
        // 3. evaluations (fiche d'évaluation agent × session)
        // --------------------------------------------------------
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('session_evaluations')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            // N+1 : agent notateur au sens CCN art. 64 (résolu à la création de la fiche)
            $table->foreignId('superieur_id')->nullable()->constrained('agents')->nullOnDelete();

            $table->date('date_evaluation')->nullable();

            // Éléments contextuels de la période
            $table->unsignedSmallInteger('jours_absence_non_justifiee')->default(0);
            $table->text('sanctions')->nullable();

            // Résultat de la notation
            $table->decimal('note_globale', 5, 2)->nullable();
            $table->string('mention', 30)->nullable(); // calculé depuis note_globale

            // Avis libre du notateur (art. 63)
            $table->text('avis_superieur')->nullable();

            // Statut du workflow (voir StatutEvaluation enum)
            $table->enum('statut', [
                'en_attente',
                'en_cours',
                'notee',
                'signee_evaluateur',
                'signee_evalue',
                'en_reclamation',
                'en_validation_rh',
                'finalisee',
                'rejetee',
                'annulee',
            ])->default('en_attente');

            // Signatures (timestamps plutôt que bool = preuve + date)
            $table->timestamp('signe_par_evaluateur_at')->nullable();
            $table->timestamp('signe_par_evalue_at')->nullable();

            // Validation RH (art. 66-70)
            $table->timestamp('date_validation_rh')->nullable();
            $table->foreignId('validateur_rh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire_rh')->nullable();
            $table->boolean('conforme_rh')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Une seule fiche par agent × session
            $table->unique(['session_id', 'agent_id']);

            $table->timestamps();
        });

        // --------------------------------------------------------
        // 4. note_evaluations (une ligne par critère noté)
        // --------------------------------------------------------
        Schema::create('note_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('question_evaluations')->cascadeOnDelete();

            // note_obtenue doit être ≤ question.bareme_max (vérification au niveau service)
            $table->decimal('note_obtenue', 5, 2);
            $table->text('commentaire')->nullable();

            // Une seule note par (fiche, critère)
            $table->unique(['evaluation_id', 'question_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_evaluations');
        Schema::dropIfExists('evaluations');
        Schema::dropIfExists('question_evaluations');
        Schema::dropIfExists('session_evaluations');
    }
};
