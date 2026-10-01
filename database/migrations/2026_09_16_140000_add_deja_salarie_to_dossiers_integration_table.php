<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers_integration', function (Blueprint $table) {
            $table->boolean('deja_salarie')->default(false)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('dossiers_integration', function (Blueprint $table) {
            $table->dropColumn('deja_salarie');
        });
    }
};
