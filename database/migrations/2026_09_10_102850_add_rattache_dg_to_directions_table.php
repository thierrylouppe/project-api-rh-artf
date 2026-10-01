<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — flag "rattaché à la DG" sur les directions.
 *
 * Quand une Direction est rattachée directement à la DG, le niveau
 * `directeur` est sauté dans la chaîne des avis hiérarchiques :
 * chef_bureau → chef_service → directeur_general (sans directeur intermédiaire).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directions', function (Blueprint $table) {
            $table->boolean('rattache_dg')->default(false)->after('description')
                ->comment('Vrai si la direction est rattachée directement à la DG (saute le niveau directeur dans la chaîne des avis)');
        });
    }

    public function down(): void
    {
        Schema::table('directions', function (Blueprint $table) {
            $table->dropColumn('rattache_dg');
        });
    }
};
