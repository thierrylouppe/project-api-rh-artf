<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_nominations', function (Blueprint $table) {
            $table->id();
            $table->enum('type_acte', ['arrete', 'decision', 'note_service'])->default('decision');
            $table->date('date_debut');
            $table->enum('statut', ['en_attente', 'approuvee', 'active', 'cloturee', 'rejetee'])->default('en_attente');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::table('nominations', function (Blueprint $table) {
            $table->foreignId('lot_nomination_id')
                ->nullable()
                ->after('created_by')
                ->constrained('lot_nominations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('nominations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lot_nomination_id');
        });

        Schema::dropIfExists('lot_nominations');
    }
};
