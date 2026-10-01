<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vague F — Cloisonnement.
 *
 * Rattache chaque utilisateur DRHL à son bureau de rattachement.
 * Nullable : les comptes admin / directeur-general n'ont pas de bureau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('bureau_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('bureaus')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bureau_id']);
            $table->dropColumn('bureau_id');
        });
    }
};
