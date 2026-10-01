<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('statut', 20)->default('brouillon');
            $table->date('date_fait');
            $table->foreignId('ayant_droit_id')->nullable()->constrained('ayants_droit')->nullOnDelete();
            $table->string('beneficiaire_libelle')->nullable();
            $table->boolean('transport_corps')->default(false);
            $table->unsignedBigInteger('montant_demande')->nullable();
            $table->unsignedBigInteger('montant_calcule')->default(0);
            $table->unsignedBigInteger('montant_accorde')->nullable();
            $table->json('calcul_snapshot')->nullable();
            $table->text('notes_instruction')->nullable();
            $table->text('commentaire_decision')->nullable();
            $table->date('date_decision')->nullable();
            $table->unsignedSmallInteger('paie_annee')->nullable();
            $table->unsignedTinyInteger('paie_mois')->nullable();
            $table->foreignId('paie_element_affectation_id')->nullable()->constrained('paie_element_affectations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('instruite_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decideur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'statut']);
            $table->index(['type', 'statut']);
        });

        Schema::create('prestation_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestation_id')->constrained('prestations')->cascadeOnDelete();
            $table->string('type_piece', 40);
            $table->string('fichier_path');
            $table->string('nom_original');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('taille')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestation_pieces');
        Schema::dropIfExists('prestations');
    }
};
