<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lot_affectations', function (Blueprint $table) {
            $table->id();
            $table->date('date_affectation');
            $table->text('motif')->nullable();
            $table->text('note_service')->nullable();
            $table->string('note_service_nom_original')->nullable();
            $table->enum('statut', ['en_attente_validation', 'approuvee', 'active', 'terminee', 'rejetee'])
                ->default('en_attente_validation');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('lot_affectation_id')
                ->nullable()
                ->after('created_by')
                ->constrained('lot_affectations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lot_affectation_id');
        });

        Schema::dropIfExists('lot_affectations');
    }
};
