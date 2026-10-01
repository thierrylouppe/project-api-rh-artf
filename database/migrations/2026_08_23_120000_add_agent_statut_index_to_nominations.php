<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->index(['agent_id', 'statut'], 'nominations_agent_id_statut_index');
        });
    }

    public function down(): void
    {
        // InnoDB réutilise cet index composite pour la clé étrangère agent_id
        // et supprime l'index simple devenu redondant. Il faut donc détacher
        // la contrainte avant de supprimer l'index, puis la recréer.
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
        });

        Schema::table('nominations', function (Blueprint $table) {
            $table->dropIndex('nominations_agent_id_statut_index');
        });

        Schema::table('nominations', function (Blueprint $table) {
            $table->foreign('agent_id')
                ->references('id')
                ->on('agents')
                ->cascadeOnDelete();
        });
    }
};
