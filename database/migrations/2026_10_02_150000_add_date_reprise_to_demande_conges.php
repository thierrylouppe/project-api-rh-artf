<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demande_conges', function (Blueprint $table) {
            $table->date('date_reprise')->nullable()->after('date_fin');
        });
    }

    public function down(): void
    {
        Schema::table('demande_conges', function (Blueprint $table) {
            $table->dropColumn('date_reprise');
        });
    }
};
