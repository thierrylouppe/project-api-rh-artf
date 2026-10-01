<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salaires_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('salaire_id')->constrained('salaires')->restrictOnDelete();
            $table->foreignId('classegrillesalariale_id')->constrained('classegrillesalariales')->restrictOnDelete();
            $table->unsignedTinyInteger('echelon');
            $table->decimal('montant_base', 12, 2);
            $table->decimal('montant_net', 12, 2)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['actif', 'cloture'])->default('actif');
            $table->timestamps();

            $table->index(['agent_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaires_agents');
    }
};
