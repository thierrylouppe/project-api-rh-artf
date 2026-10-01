<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — Avis hiérarchiques (CCN ARTF art. 64).
 *
 * Un avis par niveau hiérarchique et par fiche d'évaluation.
 * Chaîne standard : chef_bureau(1) → chef_service(2) → directeur(3) → directeur_general(4)
 * Chaîne DG       : chef_bureau(1) → chef_service(2) → directeur_general(3)  [si direction.rattache_dg]
 *
 * Signature définitive : un avis signé n'est plus modifiable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis_hierarchiques', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluation_id')->constrained('evaluations')->cascadeOnDelete();

            $table->enum('niveau', [
                'chef_bureau',
                'chef_service',
                'directeur',
                'directeur_general',
            ]);

            // Contenu de l'avis
            $table->text('avis')->nullable();
            $table->boolean('approuve')->nullable()->comment('true = favorable, false = défavorable, null = non renseigné');
            $table->text('observations')->nullable();

            // Signature définitive
            $table->boolean('signe')->default(false);
            $table->timestamp('date_signature')->nullable();
            $table->foreignId('signe_par')->nullable()->constrained('users')->nullOnDelete();

            // Ordre dans la chaîne (calculé à la création : 1-4)
            $table->unsignedTinyInteger('ordre');

            $table->timestamps();

            // Un seul avis par niveau et par fiche
            $table->unique(['evaluation_id', 'niveau']);
            $table->index(['evaluation_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_hierarchiques');
    }
};
