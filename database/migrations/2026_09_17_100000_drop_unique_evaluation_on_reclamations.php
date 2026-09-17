<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B5 — une fiche peut connaître plusieurs réclamations successives.
 *
 * `EvaluationStatutService::reclamer()` autorise explicitement une nouvelle
 * réclamation dès que la précédente est traitée (art. 65 : la RH peut accepter,
 * le notateur corrige, l'agent peut contester la nouvelle note). L'unicité posée
 * à la création de la table contredisait cette règle : la deuxième réclamation
 * partait en erreur SQL 500 au lieu du parcours métier.
 *
 * On garde un index simple : la recherche par fiche reste le seul accès.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropUnique(['evaluation_id']);
            $table->index('evaluation_id');
        });
    }

    public function down(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropIndex(['evaluation_id']);
            $table->unique(['evaluation_id']);
        });
    }
};
