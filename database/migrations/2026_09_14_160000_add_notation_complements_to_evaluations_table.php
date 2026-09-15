<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot A : affectation retenue pour la notation (CCN art. 62).
 * Lot B : inscription au tableau d'avancement (D5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('affectation_notation_id')
                ->nullable()
                ->after('superieur_id')
                ->constrained('affectations')
                ->nullOnDelete();

            $table->boolean('inscrit_tableau')
                ->default(false)
                ->after('conforme_rh');

            $table->index(['session_id', 'inscrit_tableau']);
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropIndex(['session_id', 'inscrit_tableau']);
            $table->dropConstrainedForeignId('affectation_notation_id');
            $table->dropColumn('inscrit_tableau');
        });
    }
};
