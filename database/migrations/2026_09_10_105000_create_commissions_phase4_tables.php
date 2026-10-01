<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Tables des commissions (CCN ARTF art. 68–70).
 *
 * 1. `commissions_preparatoires` : une par session — harmonise les notes N+1.
 *    Présidée par le DG (art. 68).
 *
 * 2. `commissions_avancements`   : une par session — décision finale d'avancement.
 *    Présidée par le DG (art. 69). Doit être clôturée APRÈS la commission préparatoire.
 *
 * La clôture de session n'est autorisée que si les deux commissions sont `cloturee`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Commission préparatoire (art. 68) ----
        Schema::create('commissions_preparatoires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('session_evaluations')->cascadeOnDelete();

            $table->enum('statut', ['en_cours', 'cloturee'])->default('en_cours');

            $table->date('date_ouverture');
            $table->date('date_cloture')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('cloture_par')->nullable()->constrained('users')->nullOnDelete();

            $table->text('observations')->nullable()
                ->comment('Observations générales de la commission');

            $table->timestamps();
        });

        // ---- Commission d'avancement (art. 69) ----
        Schema::create('commissions_avancements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('session_evaluations')->cascadeOnDelete();

            $table->enum('statut', ['en_cours', 'cloturee'])->default('en_cours');

            $table->date('date_ouverture');
            $table->date('date_cloture')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('cloture_par')->nullable()->constrained('users')->nullOnDelete();

            $table->text('observations')->nullable()
                ->comment('Rapport de la commission d\'avancement');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions_avancements');
        Schema::dropIfExists('commissions_preparatoires');
    }
};
