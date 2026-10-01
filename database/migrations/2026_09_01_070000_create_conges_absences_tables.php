<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jour_feries', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->date('date');
            $table->boolean('recurrent')->default(true);
            $table->timestamps();
        });

        Schema::create('regle_acquisition_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_conge_id')->constrained('type_conges')->cascadeOnDelete();
            $table->decimal('jours_par_mois', 5, 2)->default(2.5);
            $table->unsignedSmallInteger('jours_max')->nullable();
            $table->timestamps();
        });

        Schema::create('conge_soldes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('type_conge_id')->constrained('type_conges')->cascadeOnDelete();
            $table->unsignedSmallInteger('annee');
            $table->decimal('solde_initial', 6, 2);
            $table->decimal('solde_actuel', 6, 2);
            $table->timestamps();

            $table->unique(['agent_id', 'type_conge_id', 'annee']);
        });

        Schema::create('demande_conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('type_conge_id')->constrained('type_conges');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedSmallInteger('nb_jours');
            $table->text('motif')->nullable();
            $table->string('statut')->default('soumise');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valideur_n1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valideur_rh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire_n1')->nullable();
            $table->text('commentaire_rh')->nullable();
            $table->timestamp('date_validation_n1')->nullable();
            $table->timestamp('date_validation_rh')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'statut']);
        });

        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('type_absence_id')->constrained('type_absences');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedSmallInteger('nb_jours');
            $table->boolean('justifiee')->default(false);
            $table->text('motif')->nullable();
            $table->string('statut')->default('en_attente');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valideur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire_validation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
        Schema::dropIfExists('demande_conges');
        Schema::dropIfExists('conge_soldes');
        Schema::dropIfExists('regle_acquisition_conges');
        Schema::dropIfExists('jour_feries');
    }
};
