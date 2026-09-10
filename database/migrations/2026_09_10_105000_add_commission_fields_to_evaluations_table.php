<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Champs commissions sur les fiches d'évaluation (CCN ARTF art. 67–70).
 *
 * - commission_note     : note harmonisée par la commission préparatoire (peut différer de note_globale)
 * - note_synthese       : appréciation narrative rédigée en commission préparatoire (art. 67)
 * - commission_decision : décision de la commission d'avancement
 * - nombre_echelons     : nombre d'échelons accordés (0-2, même classe)
 * - note_avancement     : note définitive retenue pour l'avancement (art. 70)
 * - echelon_avance      : flag idempotent — vrai si l'échelon a été appliqué en paie
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Commission préparatoire
            $table->float('commission_note')->nullable()->after('mention')
                ->comment('Note harmonisée par la commission préparatoire — peut différer de note_globale');
            $table->text('note_synthese')->nullable()->after('commission_note')
                ->comment('Appréciation narrative rédigée en commission (art. 67)');

            // Commission d'avancement
            $table->enum('commission_decision', ['favorable', 'defavorable', 'reporte'])
                ->nullable()->after('note_synthese');
            $table->unsignedTinyInteger('nombre_echelons')->nullable()->after('commission_decision')
                ->comment('Échelons accordés (0-2, même classe, art. 70)');
            $table->float('note_avancement')->nullable()->after('nombre_echelons')
                ->comment('Note définitive retenue pour l\'avancement (art. 70) — peut différer de note_globale N+1');

            // Lien paie — idempotent
            $table->boolean('echelon_avance')->default(false)->after('note_avancement')
                ->comment('Vrai si l\'échelon a déjà été appliqué (idempotent)');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'commission_note',
                'note_synthese',
                'commission_decision',
                'nombre_echelons',
                'note_avancement',
                'echelon_avance',
            ]);
        });
    }
};
