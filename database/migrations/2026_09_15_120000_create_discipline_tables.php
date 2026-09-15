<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_sanctions', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('gravite', 20);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('type_sanction_id')->constrained('type_sanctions');
            $table->text('motif');
            $table->date('date_faits');
            $table->text('notes_instruction')->nullable();
            $table->date('date_decision')->nullable();
            $table->text('decision')->nullable();
            $table->string('statut', 20)->default('en_attente');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('commentaire_validation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_id', 'statut']);
        });

        Schema::create('avertissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->text('motif');
            $table->date('date');
            $table->foreignId('emetteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avertissements');
        Schema::dropIfExists('sanctions');
        Schema::dropIfExists('type_sanctions');
    }
};
