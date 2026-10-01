<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organismes_sociaux', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('code', 50)->nullable()->unique();
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('affiliations_sociales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('organisme_id')->constrained('organismes_sociaux')->restrictOnDelete();
            $table->string('numero_affiliation', 50);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['agent_id', 'statut']);
            $table->index(['organisme_id', 'statut']);
        });

        Schema::create('ayants_droit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('sexe', 1)->nullable();
            $table->string('lien_juridique', 30);
            $table->string('qualite_age', 20)->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['agent_id', 'type', 'actif']);
        });

        Schema::create('ayant_droit_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ayant_droit_id')->constrained('ayants_droit')->cascadeOnDelete();
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
        Schema::dropIfExists('ayant_droit_pieces');
        Schema::dropIfExists('ayants_droit');
        Schema::dropIfExists('affiliations_sociales');
        Schema::dropIfExists('organismes_sociaux');
    }
};
