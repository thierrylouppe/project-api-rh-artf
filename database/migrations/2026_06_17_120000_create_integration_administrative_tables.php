<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. Agents
        // ─────────────────────────────────────────────
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('matricule')->unique()->nullable();
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('lieu_naissance')->nullable();
            $table->string('nationalite')->default('Congolaise');
            $table->enum('genre', ['M', 'F']);
            $table->string('telephone')->nullable();
            $table->string('email_personnel')->nullable();
            $table->string('email_professionnel')->nullable();
            $table->string('badge_numero')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('numero_cnss')->nullable();
            $table->string('rib_bancaire')->nullable();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->foreignId('categorie_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('echelon_id')->nullable()->constrained('echelons')->nullOnDelete();
            $table->foreignId('fonction_id')->nullable()->constrained('fonctions')->nullOnDelete();
            $table->foreignId('type_integration_id')->nullable()->constrained('type_integrations')->nullOnDelete();
            $table->date('date_prise_service')->nullable();
            $table->enum('statut', ['actif', 'inactif', 'suspendu', 'retraite'])->default('actif');
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. Dossiers d'intégration
        // ─────────────────────────────────────────────
        Schema::create('dossiers_integration', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('type_integration_id')->constrained('type_integrations');
            $table->foreignId('demandeur_id')->constrained('users');
            $table->string('structurable_type')->nullable();
            $table->unsignedBigInteger('structurable_id')->nullable();
            $table->string('poste_demande')->nullable();
            $table->unsignedTinyInteger('nombre_postes')->default(1);
            $table->string('statut')->default('BROUILLON');
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->date('date_demande');
            $table->text('motif')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['structurable_type', 'structurable_id']);
        });

        // ─────────────────────────────────────────────
        // 3. Documents du dossier
        // ─────────────────────────────────────────────
        Schema::create('documents_dossier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_integration_id')->constrained('dossiers_integration')->cascadeOnDelete();
            $table->foreignId('type_document_id')->constrained('type_documents');
            $table->string('nom_original');
            $table->string('chemin_fichier');
            $table->boolean('est_obligatoire')->default(false);
            $table->boolean('est_valide')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_validation')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 4. Circuit de validation (polymorphique)
        //    Couvre : DossierIntegration, Affectation, Nomination
        // ─────────────────────────────────────────────
        Schema::create('validations_workflow', function (Blueprint $table) {
            $table->id();
            $table->morphs('validable');
            $table->string('niveau');
            $table->unsignedTinyInteger('ordre');
            $table->foreignId('validateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('statut', ['en_attente', 'approuve', 'rejete', 'renvoye'])->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->timestamp('date_decision')->nullable();
            $table->timestamps();

            $table->index(['validable_type', 'validable_id', 'statut']);
        });

        // ─────────────────────────────────────────────
        // 5. Actes administratifs
        // ─────────────────────────────────────────────
        Schema::create('actes_administratifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_integration_id')->constrained('dossiers_integration')->cascadeOnDelete();
            $table->string('type_acte');
            $table->string('numero')->unique();
            $table->text('contenu')->nullable();
            $table->string('fichier_path')->nullable();
            $table->boolean('signe')->default(false);
            $table->foreignId('signe_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('date_signature')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 6. Contrats
        // ─────────────────────────────────────────────
        Schema::create('contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('type_contrat_id')->constrained('type_contrats');
            $table->foreignId('dossier_integration_id')->nullable()->constrained('dossiers_integration')->nullOnDelete();
            $table->foreignId('fonction_id')->nullable()->constrained('fonctions')->nullOnDelete();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('remuneration', 12, 2)->default(0);
            $table->enum('statut', ['actif', 'expire', 'resilie'])->default('actif');
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 7. Affectations
        // ─────────────────────────────────────────────
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('structurable_type');
            $table->unsignedBigInteger('structurable_id');
            $table->text('motif')->nullable();
            $table->text('note_service')->nullable();
            $table->foreignId('superieur_hierarchique_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->date('date_affectation');
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['en_attente_validation', 'approuvee', 'active', 'terminee', 'rejetee'])->default('en_attente_validation');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['structurable_type', 'structurable_id']);
        });

        // ─────────────────────────────────────────────
        // 8. Nominations
        // ─────────────────────────────────────────────
        Schema::create('nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('poste');
            $table->string('structurable_type');
            $table->unsignedBigInteger('structurable_id');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('type_acte', ['arrete', 'decision', 'note_service'])->default('decision');
            $table->enum('statut', ['en_attente', 'approuvee', 'active', 'cloturee', 'rejetee'])->default('en_attente');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['structurable_type', 'structurable_id', 'statut']);
        });

        // ─────────────────────────────────────────────
        // 9. Comptes intégration (provisioning)
        // ─────────────────────────────────────────────
        Schema::create('comptes_integration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained('agents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('login')->unique();
            $table->string('email_professionnel')->unique();
            $table->string('badge_numero')->nullable();
            $table->boolean('mot_de_passe_provisoire_envoye')->default(false);
            $table->timestamp('date_creation')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 10. Remises de matériel
        // ─────────────────────────────────────────────
        Schema::create('remises_materiel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('affectation_id')->nullable()->constrained('affectations')->nullOnDelete();
            $table->json('materiel');
            $table->date('date_remise');
            $table->foreignId('remis_par')->constrained('users');
            $table->string('pv_path')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 11. Prises de service
        // ─────────────────────────────────────────────
        Schema::create('prises_de_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('dossier_integration_id')->nullable()->constrained('dossiers_integration')->nullOnDelete();
            $table->foreignId('responsable_id')->constrained('agents');
            $table->date('date_prise_service');
            $table->boolean('confirmation_presence')->default(false);
            $table->boolean('confirmation_installation')->default(false);
            $table->boolean('confirmation_equipements')->default(false);
            $table->string('pv_path')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 12. Historique & traçabilité (polymorphique)
        // ─────────────────────────────────────────────
        Schema::create('historiques_integration', function (Blueprint $table) {
            $table->id();
            $table->string('historiable_type');
            $table->unsignedBigInteger('historiable_id');
            $table->foreignId('utilisateur_id')->constrained('users');
            $table->string('action');
            $table->json('ancienne_valeur')->nullable();
            $table->json('nouvelle_valeur')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['historiable_type', 'historiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historiques_integration');
        Schema::dropIfExists('prises_de_service');
        Schema::dropIfExists('remises_materiel');
        Schema::dropIfExists('comptes_integration');
        Schema::dropIfExists('nominations');
        Schema::dropIfExists('affectations');
        Schema::dropIfExists('contrats');
        Schema::dropIfExists('actes_administratifs');
        Schema::dropIfExists('validations_workflow');
        Schema::dropIfExists('documents_dossier');
        Schema::dropIfExists('dossiers_integration');
        Schema::dropIfExists('agents');
    }
};
