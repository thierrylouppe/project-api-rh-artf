<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5.1 — Art. 71 CCN ARTF : bonification de +2 échelons automatique.
 *
 * Condition : stage ≥ 9 mois autorisé par l'employeur,
 * sur certificat ou attestation de fin de stage.
 * Parcours SÉPARÉ du cycle 24 mois.
 *
 * Idempotence : `applique_le` non null = déjà appliqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonifications_stage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            // Stage
            $table->date('date_debut_stage');
            $table->date('date_fin_stage');
            $table->unsignedSmallInteger('duree_mois')
                ->comment('Durée calculée en mois. Doit être ≥ 9 pour valider (art. 71).');

            $table->enum('type_document', ['certificat', 'attestation'])
                ->comment('Pièce justificative produite par l\'agent');
            $table->string('reference_document')->nullable()
                ->comment('Référence / intitulé du certificat ou attestation');

            // Nombre d'échelons accordés (toujours 2 selon art. 71, mais paramétrable)
            $table->unsignedTinyInteger('nb_echelons')->default(2);

            // Workflow validation
            $table->enum('statut', ['en_attente', 'approuvee', 'rejetee'])->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();

            // Application en paie (idempotent)
            $table->timestamp('applique_le')->nullable()
                ->comment('Non null = échelon(s) déjà appliqué(s). Idempotent.');
            $table->foreignId('applique_par')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonifications_stage');
    }
};
