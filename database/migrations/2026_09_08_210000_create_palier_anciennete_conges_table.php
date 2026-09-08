<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('palier_anciennete_conges', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anciennete_min');
            $table->unsignedSmallInteger('anciennete_max')->nullable();
            $table->unsignedSmallInteger('jours_bonus');
            $table->timestamps();
        });

        Schema::table('conge_soldes', function (Blueprint $table) {
            $table->decimal('jours_anciennete', 6, 2)->default(0)->after('solde_actuel');
        });
    }

    public function down(): void
    {
        Schema::table('conge_soldes', function (Blueprint $table) {
            $table->dropColumn('jours_anciennete');
        });

        Schema::dropIfExists('palier_anciennete_conges');
    }
};
