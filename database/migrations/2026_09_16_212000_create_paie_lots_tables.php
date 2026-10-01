<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee');
            $table->unsignedTinyInteger('mois');
            $table->string('statut')->default('brouillon');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('controle_at')->nullable();
            $table->foreignId('controle_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cloture_at')->nullable();
            $table->foreignId('cloture_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire')->nullable();
            $table->json('anomalies')->nullable();
            $table->decimal('total_gains', 14, 2)->default(0);
            $table->decimal('total_retenues', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->unsignedInteger('nb_lignes')->default(0);
            $table->timestamps();

            $table->unique(['annee', 'mois']);
            $table->index('statut');
        });

        Schema::create('paie_lot_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('paie_lots')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->restrictOnDelete();
            $table->foreignId('salaire_agent_id')->nullable()->constrained('salaires_agents')->restrictOnDelete();
            $table->boolean('hors_grille')->default(false);
            $table->decimal('montant_base', 14, 2)->default(0);
            $table->decimal('total_gains', 14, 2)->default(0);
            $table->decimal('total_retenues', 14, 2)->default(0);
            $table->decimal('montant_net', 14, 2)->default(0);
            $table->unsignedInteger('nb_anomalies')->default(0);
            $table->json('snapshot_agent')->nullable();
            $table->timestamps();

            $table->unique(['lot_id', 'agent_id']);
        });

        Schema::create('paie_lot_ligne_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ligne_id')->constrained('paie_lot_lignes')->cascadeOnDelete();
            $table->foreignId('paie_element_id')->nullable()->constrained('paie_elements')->nullOnDelete();
            $table->string('code');
            $table->string('libelle');
            $table->string('nature')->nullable();
            $table->string('sens');
            $table->decimal('montant', 14, 2);
            $table->string('source');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['ligne_id', 'sens']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_lot_ligne_details');
        Schema::dropIfExists('paie_lot_lignes');
        Schema::dropIfExists('paie_lots');
    }
};
