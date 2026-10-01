<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions_conventionnelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type');
            $table->string('statut')->default('soumise');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('organisme_accueil')->nullable();
            $table->boolean('consentement_agent')->default(false);
            $table->boolean('detachement_office')->default(false);
            $table->unsignedTinyInteger('nb_renouvellements')->default(0);
            $table->foreignId('decision_dg_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->string('piece_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'statut']);
            $table->index(['type', 'statut']);
            $table->index('date_fin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions_conventionnelles');
    }
};
