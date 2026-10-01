<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reclassements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('statut', 20)->default('soumis');

            $table->foreignId('classe_origine_id')->nullable()->constrained('classegrillesalariales')->nullOnDelete();
            $table->foreignId('classe_cible_id')->nullable()->constrained('classegrillesalariales')->nullOnDelete();
            $table->foreignId('fonction_cible_id')->nullable()->constrained('fonctions')->nullOnDelete();
            $table->foreignId('diplome_id')->nullable()->constrained('diplomes')->nullOnDelete();

            $table->text('motif');
            $table->string('motif_reconversion', 40)->nullable();
            $table->string('piece_path')->nullable();

            $table->unsignedTinyInteger('age_ans')->nullable();
            $table->unsignedTinyInteger('anciennete_ans')->nullable();
            $table->unsignedTinyInteger('annees_dans_classe')->nullable();
            $table->unsignedTinyInteger('echelon_origine')->nullable();
            $table->unsignedTinyInteger('echelon_cible')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('applique_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applique_at')->nullable();

            $table->timestamps();

            $table->index(['agent_id', 'statut']);
            $table->index(['type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reclassements');
    }
};
