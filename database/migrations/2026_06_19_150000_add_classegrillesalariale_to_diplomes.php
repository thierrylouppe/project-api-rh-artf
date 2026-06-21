<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diplomes', function (Blueprint $table) {
            $table->foreignId('classegrillesalariale_id')
                  ->nullable()
                  ->after('description')
                  ->constrained('classegrillesalariales')
                  ->nullOnDelete()
                  ->comment('Classe de la grille salariale correspondant à ce diplôme');
        });
    }

    public function down(): void
    {
        Schema::table('diplomes', function (Blueprint $table) {
            $table->dropForeign(['classegrillesalariale_id']);
            $table->dropColumn('classegrillesalariale_id');
        });
    }
};
