<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_integrations', function (Blueprint $table) {
            $table->boolean('necessite_validation_dg')->default(true)->after('necessite_contrat');
            $table->boolean('necessite_compte_utilisateur')->default(true)->after('necessite_validation_dg');
            $table->string('prefixe_matricule')->default('ARTF')->after('necessite_compte_utilisateur');
            $table->json('documents_obligatoires')->nullable()->after('prefixe_matricule');
            $table->unsignedTinyInteger('duree_max_mois')->nullable()->after('documents_obligatoires');
        });
    }

    public function down(): void
    {
        Schema::table('type_integrations', function (Blueprint $table) {
            $table->dropColumn([
                'necessite_validation_dg',
                'necessite_compte_utilisateur',
                'prefixe_matricule',
                'documents_obligatoires',
                'duree_max_mois',
            ]);
        });
    }
};
