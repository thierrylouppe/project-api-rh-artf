<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paie_elements', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('libelle');
            $table->string('nature');
            $table->string('sens');
            $table->string('periodicite');
            $table->string('mode_calcul');
            $table->decimal('montant_defaut', 14, 2)->nullable();
            $table->decimal('taux_defaut', 8, 4)->nullable();
            $table->string('article_ccn')->nullable();
            $table->json('fonction_sigles')->nullable();
            $table->json('mois_declenchement')->nullable();
            $table->boolean('actif')->default(true);
            $table->boolean('systeme')->default(false);
            $table->timestamps();

            $table->index('actif');
            $table->index('nature');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paie_elements');
    }
};
