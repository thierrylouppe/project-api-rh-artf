<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('informations_personnelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained('agents')->cascadeOnDelete();
            $table->string('adresse')->nullable();
            $table->string('quartier')->nullable();
            $table->string('ville')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('pays')->default('Congo');
            $table->timestamps();
        });

        Schema::create('informations_professionnelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained('agents')->cascadeOnDelete();
            $table->foreignId('diplome_id')->nullable()->constrained('diplomes')->nullOnDelete();
            $table->string('niveau_etude')->nullable();
            $table->string('specialite')->nullable();
            $table->unsignedSmallInteger('annees_experience')->nullable();
            $table->string('etablissement')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts_urgence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone');
            $table->string('relation')->nullable();
            $table->timestamps();
        });

        Schema::create('situations_familiales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained('agents')->cascadeOnDelete();
            $table->string('statut_matrimonial')->nullable();
            $table->unsignedTinyInteger('nb_enfants')->default(0);
            $table->timestamps();
        });

        Schema::create('documents_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('type_document_id')->constrained('type_documents');
            $table->string('titre')->nullable();
            $table->string('sous_dossier')->default('general');
            $table->string('chemin_fichier');
            $table->string('nom_original');
            $table->unsignedBigInteger('taille')->nullable();
            $table->string('mime_type')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_id', 'sous_dossier']);
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('statut');
            $table->foreignId('archived_by')->nullable()->after('archived_at')->constrained('users')->nullOnDelete();
            $table->text('motif_archivage')->nullable()->after('archived_by');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite','stagiaire','archive') NOT NULL DEFAULT 'actif'");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE agents SET statut = 'inactif' WHERE statut = 'archive'");
            DB::statement("ALTER TABLE agents MODIFY COLUMN statut ENUM('actif','inactif','suspendu','retraite','stagiaire') NOT NULL DEFAULT 'actif'");
        }

        Schema::table('agents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn(['archived_at', 'motif_archivage']);
        });

        Schema::dropIfExists('documents_agents');
        Schema::dropIfExists('situations_familiales');
        Schema::dropIfExists('contacts_urgence');
        Schema::dropIfExists('informations_professionnelles');
        Schema::dropIfExists('informations_personnelles');
    }
};
