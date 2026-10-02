<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagne_conge_annuels', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('annee')->unique();
            $table->date('date_ouverture');
            $table->date('date_cloture');
            $table->timestamp('date_cloture_effective')->nullable();
            $table->string('statut')->default('brouillon');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_conge_annuels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->unsignedSmallInteger('annee_source');
            $table->unsignedSmallInteger('annee_cible');
            $table->decimal('jours', 6, 2);
            $table->text('motif');
            $table->string('statut')->default('propose');
            $table->foreignId('propose_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire_decision')->nullable();
            $table->timestamp('date_decision')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'annee_source', 'statut']);
        });

        Schema::table('demande_conges', function (Blueprint $table) {
            $table->foreignId('campagne_conge_annuel_id')
                ->nullable()
                ->after('type_conge_id')
                ->constrained('campagne_conge_annuels')
                ->nullOnDelete();
            $table->string('origine')->nullable()->after('campagne_conge_annuel_id');
        });

        Schema::table('conge_soldes', function (Blueprint $table) {
            $table->decimal('jours_reportes', 6, 2)->default(0)->after('jours_anciennete');
        });
    }

    public function down(): void
    {
        Schema::table('conge_soldes', function (Blueprint $table) {
            $table->dropColumn('jours_reportes');
        });

        Schema::table('demande_conges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campagne_conge_annuel_id');
            $table->dropColumn('origine');
        });

        Schema::dropIfExists('report_conge_annuels');
        Schema::dropIfExists('campagne_conge_annuels');
    }
};
