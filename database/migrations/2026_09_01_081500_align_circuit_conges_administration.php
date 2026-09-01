<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_conges', function (Blueprint $table) {
            $table->boolean('necessite_n1')->default(true)->after('jours_max');
            $table->boolean('necessite_rh')->default(true)->after('necessite_n1');
            $table->boolean('necessite_dg')->default(false)->after('necessite_rh');
            $table->boolean('debite_solde')->default(true)->after('necessite_dg');
            $table->boolean('justificatif_requis')->default(false)->after('debite_solde');
        });

        Schema::table('demande_conges', function (Blueprint $table) {
            $table->string('justificatif_path')->nullable()->after('motif');
            $table->string('justificatif_nom_original')->nullable()->after('justificatif_path');
            $table->foreignId('valideur_dg_id')->nullable()->after('valideur_rh_id')->constrained('users')->nullOnDelete();
            $table->text('commentaire_dg')->nullable()->after('commentaire_rh');
            $table->timestamp('date_validation_dg')->nullable()->after('date_validation_rh');
        });
    }

    public function down(): void
    {
        Schema::table('demande_conges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('valideur_dg_id');
            $table->dropColumn([
                'justificatif_path',
                'justificatif_nom_original',
                'commentaire_dg',
                'date_validation_dg',
            ]);
        });

        Schema::table('type_conges', function (Blueprint $table) {
            $table->dropColumn([
                'necessite_n1',
                'necessite_rh',
                'necessite_dg',
                'debite_solde',
                'justificatif_requis',
            ]);
        });
    }
};
