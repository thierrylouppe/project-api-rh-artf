<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('type_integrations', function (Blueprint $table) {
            $table->string('type_acte_administratif')->nullable()->after('description')
                ->comment('Valeur de TypeActeAdministratif générée automatiquement pour ce type');
            $table->boolean('necessite_contrat')->default(false)->after('type_acte_administratif')
                ->comment('Vrai si ce type d\'intégration génère un contrat de travail');
        });
    }

    public function down(): void
    {
        Schema::table('type_integrations', function (Blueprint $table) {
            $table->dropColumn(['type_acte_administratif', 'necessite_contrat']);
        });
    }
};
