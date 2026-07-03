<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->string('note_service_nom_original')->nullable()->after('note_service');
        });
    }

    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropColumn('note_service_nom_original');
        });
    }
};
