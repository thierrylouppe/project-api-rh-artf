<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_formations', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('organisme')->nullable();
            $table->string('lieu')->nullable();
            $table->string('modalite', 20)->default('interne');
            $table->string('type_action', 30);
            $table->unsignedSmallInteger('duree_jours')->default(1);
            $table->decimal('cout', 12, 2)->nullable();
            $table->unsignedTinyInteger('anciennete_min_ans')->default(3);
            $table->unsignedTinyInteger('debit_formation_mois')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('plans_formation', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee')->unique();
            $table->string('titre')->nullable();
            $table->text('description')->nullable();
            $table->string('statut', 20)->default('brouillon');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_formation_lignes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans_formation')->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained('catalogue_formations')->restrictOnDelete();
            $table->unsignedSmallInteger('places_prevues')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'formation_id']);
        });

        Schema::create('inscriptions_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained('catalogue_formations')->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans_formation')->nullOnDelete();
            $table->date('date_inscription');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('inscrite');
            $table->boolean('admission_sur_titre')->default(false);
            $table->boolean('rapport_remis')->default(false);
            $table->date('debit_jusqu_au')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('certifications_formation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained('catalogue_formations')->restrictOnDelete();
            $table->foreignId('inscription_id')->nullable()->constrained('inscriptions_formation')->nullOnDelete();
            $table->foreignId('diplome_id')->nullable()->constrained('diplomes')->nullOnDelete();
            $table->date('date_obtention');
            $table->string('reference')->nullable();
            $table->string('fichier_path')->nullable();
            $table->string('nom_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('taille')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('conventions_stage', function (Blueprint $table) {
            $table->foreignId('dossier_conversion_id')
                ->nullable()
                ->after('appreciation')
                ->constrained('dossiers_integration')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conventions_stage', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dossier_conversion_id');
        });
        Schema::dropIfExists('certifications_formation');
        Schema::dropIfExists('inscriptions_formation');
        Schema::dropIfExists('plan_formation_lignes');
        Schema::dropIfExists('plans_formation');
        Schema::dropIfExists('catalogue_formations');
    }
};
