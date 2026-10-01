<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diplomes', function (Blueprint $table) {
            $table->unsignedTinyInteger('bonification_echelons')
                ->default(0)
                ->after('classegrillesalariale_id');
        });
    }

    public function down(): void
    {
        Schema::table('diplomes', function (Blueprint $table) {
            $table->dropColumn('bonification_echelons');
        });
    }
};
