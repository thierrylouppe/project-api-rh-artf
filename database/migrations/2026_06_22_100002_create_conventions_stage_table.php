<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conventions_stage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('contrat_id')->nullable()->constrained('contrats')->nullOnDelete();
            $table->foreignId('dossier_integration_id')->constrained('dossiers_integration')->cascadeOnDelete();
            $table->foreignId('tuteur_interne_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->enum('type_stage', ['academique', 'professionnel', 'qualification']);
            $table->string('etablissement');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut_stage', ['EN_COURS', 'TERMINE', 'ROMPU'])->default('EN_COURS');
            $table->decimal('note_finale', 4, 2)->nullable();
            $table->text('appreciation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conventions_stage');
    }
};
