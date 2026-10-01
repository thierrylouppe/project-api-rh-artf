<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaires_agents', function (Blueprint $table) {
            $table->string('type_changement', 40)->nullable()->after('statut');
            $table->string('motif')->nullable()->after('type_changement');
        });
    }

    public function down(): void
    {
        Schema::table('salaires_agents', function (Blueprint $table) {
            $table->dropColumn(['type_changement', 'motif']);
        });
    }
};
