<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_sanctions', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->unique()->after('nom');
            $table->boolean('exige_nb_jours')->default(false)->after('gravite');
            $table->unsignedTinyInteger('nb_jours_min')->nullable()->after('exige_nb_jours');
            $table->unsignedTinyInteger('nb_jours_max')->nullable()->after('nb_jours_min');
            $table->boolean('actif')->default(true)->after('description');
        });

        Schema::table('sanctions', function (Blueprint $table) {
            $table->unsignedTinyInteger('nb_jours')->nullable()->after('date_faits');
            $table->boolean('avec_indemnite')->nullable()->after('nb_jours');
            $table->date('date_debut_effet')->nullable()->after('avec_indemnite');
            $table->date('date_fin_effet')->nullable()->after('date_debut_effet');
        });

        Schema::create('sanction_pieces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sanction_id')->constrained('sanctions')->cascadeOnDelete();
            $table->string('fichier_path');
            $table->string('nom_original');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('taille')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanction_pieces');

        Schema::table('sanctions', function (Blueprint $table) {
            $table->dropColumn(['nb_jours', 'avec_indemnite', 'date_debut_effet', 'date_fin_effet']);
        });

        Schema::table('type_sanctions', function (Blueprint $table) {
            $table->dropColumn(['code', 'exige_nb_jours', 'nb_jours_min', 'nb_jours_max', 'actif']);
        });
    }
};
