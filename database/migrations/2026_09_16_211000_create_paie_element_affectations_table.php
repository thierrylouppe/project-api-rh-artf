<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_element_affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paie_element_id')->constrained('paie_elements')->restrictOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->decimal('montant', 14, 2)->nullable();
            $table->decimal('taux', 8, 4)->nullable();
            $table->decimal('quantite', 8, 2)->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->text('motif')->nullable();
            $table->boolean('prolongation_dg')->default(false);
            $table->json('meta')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'paie_element_id', 'date_debut'], 'paie_aff_agent_element_debut_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_element_affectations');
    }
};
