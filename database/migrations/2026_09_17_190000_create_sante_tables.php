<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structures_sanitaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type', 40);
            $table->string('ville')->nullable();
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['type', 'actif']);
        });

        Schema::create('visites_medicales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 30);
            $table->date('date_visite');
            $table->foreignId('structure_sanitaire_id')->constrained('structures_sanitaires')->restrictOnDelete();
            $table->text('observations')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'type', 'date_visite']);
        });

        Schema::create('prises_en_charge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('statut', 20)->default('brouillon');
            $table->date('date_soins');
            $table->foreignId('ayant_droit_id')->nullable()->constrained('ayants_droit')->nullOnDelete();
            $table->foreignId('structure_sanitaire_id')->constrained('structures_sanitaires')->restrictOnDelete();
            $table->unsignedBigInteger('montant_facture')->nullable();
            $table->unsignedBigInteger('montant_calcule')->default(0);
            $table->unsignedBigInteger('montant_accorde')->nullable();
            $table->json('calcul_snapshot')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('lieu')->nullable();
            $table->boolean('at_mp')->default(false);
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

        Schema::create('prise_en_charge_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prise_en_charge_id')->constrained('prises_en_charge')->cascadeOnDelete();
            $table->string('type_piece', 40);
            $table->string('fichier_path');
            $table->string('nom_original');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('taille')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('arrets_sante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('nature', 40);
            $table->string('statut', 20)->default('brouillon');
            $table->date('date_fait');
            $table->date('date_notification')->nullable();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->foreignId('structure_sanitaire_id')->constrained('structures_sanitaires')->restrictOnDelete();
            $table->foreignId('demande_conge_id')->nullable()->constrained('demande_conges')->nullOnDelete();
            $table->boolean('alerte_72h')->default(false);
            $table->unsignedInteger('nb_mois')->default(0);
            $table->unsignedInteger('nb_mois_majoration')->default(0);
            $table->unsignedBigInteger('montant_mensuel')->default(0);
            $table->unsignedBigInteger('montant_mensuel_demi')->nullable();
            $table->json('calcul_snapshot')->nullable();
            $table->text('notes_instruction')->nullable();
            $table->text('commentaire_decision')->nullable();
            $table->date('date_decision')->nullable();
            $table->foreignId('paie_element_affectation_id')->nullable()->constrained('paie_element_affectations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('instruite_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decideur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'statut']);
            $table->index(['nature', 'statut']);
        });

        Schema::create('arret_sante_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('arret_sante_id')->constrained('arrets_sante')->cascadeOnDelete();
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
        Schema::dropIfExists('arret_sante_pieces');
        Schema::dropIfExists('arrets_sante');
        Schema::dropIfExists('prise_en_charge_pieces');
        Schema::dropIfExists('prises_en_charge');
        Schema::dropIfExists('visites_medicales');
        Schema::dropIfExists('structures_sanitaires');
    }
};
