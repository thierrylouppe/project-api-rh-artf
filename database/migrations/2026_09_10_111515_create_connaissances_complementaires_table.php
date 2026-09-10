<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5.3 — Connaissances complémentaires liées à la fiche d'évaluation.
 *
 * Demandes de formation / certification identifiées pendant l'évaluation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connaissances_complementaires', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();

            $table->enum('type', ['formation', 'certification', 'perfectionnement', 'autre'])
                ->default('formation');
            $table->string('domaine')
                ->comment('Domaine / thème de la formation identifiée');
            $table->text('description')->nullable();
            $table->boolean('urgent')->default(false)
                ->comment('Besoin urgent (à planifier dans la session suivante)');

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connaissances_complementaires');
    }
};
