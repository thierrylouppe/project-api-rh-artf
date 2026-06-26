<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actes_administratifs', function (Blueprint $table) {
            $table->unique(
                ['dossier_integration_id', 'type_acte'],
                'actes_unique_type_par_dossier'
            );
        });
    }

    public function down(): void
    {
        Schema::table('actes_administratifs', function (Blueprint $table) {
            $table->dropUnique('actes_unique_type_par_dossier');
        });
    }
};
