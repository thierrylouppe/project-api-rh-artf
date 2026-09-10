<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5.2 — Art. 72 CCN ARTF : avancement exceptionnel.
 *
 * La commission d'avancement, sur proposition du DG, peut accorder
 * un avancement exceptionnel dans la limite de 2 échelons.
 *
 * Peut être lié à une commission d'avancement (session) ou accordé hors session.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avancements_exceptionnels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            // Peut être rattaché à une commission d'avancement
            $table->foreignId('commission_avancement_id')->nullable()
                ->constrained('commissions_avancements')->nullOnDelete();

            $table->unsignedTinyInteger('nb_echelons')
                ->comment('Nombre d\'échelons accordés : 1 ou 2 (art. 72)');
            $table->text('motif')
                ->comment('Justification de la proposition (obligatoire)');

            // Proposition DG
            $table->foreignId('propose_par')->constrained('users');
            $table->date('date_proposition');

            // Décision
            $table->enum('statut', ['en_attente', 'approuvee', 'rejetee'])->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();

            // Application en paie (idempotent)
            $table->timestamp('applique_le')->nullable()
                ->comment('Non null = échelon(s) déjà appliqué(s). Idempotent.');
            $table->foreignId('applique_par')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avancements_exceptionnels');
    }
};
