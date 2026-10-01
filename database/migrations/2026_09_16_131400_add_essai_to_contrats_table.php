<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->date('date_debut_essai')->nullable()->after('statut');
            $table->date('date_fin_essai')->nullable()->after('date_debut_essai');
            $table->unsignedTinyInteger('duree_essai_mois')->nullable()->after('date_fin_essai');
            $table->boolean('essai_renouvele')->default(false)->after('duree_essai_mois');
            $table->string('statut_essai', 30)->nullable()->after('essai_renouvele');
            $table->date('date_confirmation_essai')->nullable()->after('statut_essai');
            $table->string('lieu_recrutement')->nullable()->after('date_confirmation_essai');
        });
    }

    public function down(): void
    {
        Schema::table('contrats', function (Blueprint $table) {
            $table->dropColumn([
                'date_debut_essai',
                'date_fin_essai',
                'duree_essai_mois',
                'essai_renouvele',
                'statut_essai',
                'date_confirmation_essai',
                'lieu_recrutement',
            ]);
        });
    }
};
