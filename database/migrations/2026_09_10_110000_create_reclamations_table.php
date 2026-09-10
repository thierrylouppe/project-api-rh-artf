<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — Réclamations (CCN ARTF art. 65)
 *
 * Un agent peut contester sa note après que le notateur a signé.
 * La réclamation est traitée par la RH.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();

            // Motif obligatoire : min 10 caractères (validé au niveau service)
            $table->text('motif');

            $table->enum('statut', ['en_attente', 'acceptee', 'rejetee'])->default('en_attente');

            // Traitement RH
            $table->text('commentaire_rh')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();

            $table->timestamps();

            // Une seule réclamation active par fiche (unicité métier)
            $table->unique(['evaluation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};
