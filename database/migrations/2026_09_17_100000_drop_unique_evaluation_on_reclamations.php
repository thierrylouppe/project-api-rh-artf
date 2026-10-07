<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'unicité sur evaluation_id empêche une nouvelle réclamation
 * une fois la précédente traitée. Sous MySQL/InnoDB, cet index unique
 * est aussi celui de la clé étrangère : il faut retirer la contrainte
 * avant l'index, puis la recréer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropForeign(['evaluation_id']);
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropUnique(['evaluation_id']);
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->foreign('evaluation_id')
                ->references('id')
                ->on('evaluations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reclamations', function (Blueprint $table) {
            $table->dropForeign(['evaluation_id']);
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->unique(['evaluation_id']);
        });

        Schema::table('reclamations', function (Blueprint $table) {
            $table->foreign('evaluation_id')
                ->references('id')
                ->on('evaluations')
                ->cascadeOnDelete();
        });
    }
};
