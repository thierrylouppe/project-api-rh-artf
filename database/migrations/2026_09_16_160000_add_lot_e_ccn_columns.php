<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('motif_archivage_code')->nullable()->after('motif_archivage');
            $table->date('prioritaire_reembauche_jusquau')->nullable()->after('motif_archivage_code');
            $table->index('prioritaire_reembauche_jusquau');
        });

        Schema::table('nominations', function (Blueprint $table) {
            $table->boolean('soumis_a_essai')->default(false)->after('type_acte');
            $table->string('essai_statut')->nullable()->after('soumis_a_essai');
            $table->unsignedTinyInteger('duree_essai_mois')->nullable()->after('essai_statut');
            $table->date('date_debut_essai')->nullable()->after('duree_essai_mois');
            $table->date('date_fin_essai')->nullable()->after('date_debut_essai');
            $table->date('date_confirmation_essai')->nullable()->after('date_fin_essai');
            $table->foreignId('classegrillesalariale_id')->nullable()->after('date_confirmation_essai')
                ->constrained('classegrillesalariales')->nullOnDelete();
            $table->foreignId('nomination_precedente_id')->nullable()->after('classegrillesalariale_id')
                ->constrained('nominations')->nullOnDelete();
            $table->json('snapshot_carriere')->nullable()->after('nomination_precedente_id');
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->string('motif_code')->nullable()->after('motif');
            $table->text('commentaire_opportunite')->nullable()->after('motif_code');
            $table->json('pieces_rapprochement')->nullable()->after('commentaire_opportunite');
        });
    }

    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropColumn(['motif_code', 'commentaire_opportunite', 'pieces_rapprochement']);
        });

        Schema::table('nominations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nomination_precedente_id');
            $table->dropConstrainedForeignId('classegrillesalariale_id');
            $table->dropColumn([
                'soumis_a_essai',
                'essai_statut',
                'duree_essai_mois',
                'date_debut_essai',
                'date_fin_essai',
                'date_confirmation_essai',
                'snapshot_carriere',
            ]);
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->dropIndex(['prioritaire_reembauche_jusquau']);
            $table->dropColumn(['motif_archivage_code', 'prioritaire_reembauche_jusquau']);
        });
    }
};
