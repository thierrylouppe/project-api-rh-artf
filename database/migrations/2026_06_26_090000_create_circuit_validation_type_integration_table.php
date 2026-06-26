<?php

use App\Enums\NiveauValidation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circuit_validation_type_integration', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_integration_id')
                ->constrained('type_integrations')
                ->cascadeOnDelete();
            $table->string('niveau');
            $table->unsignedTinyInteger('ordre');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['type_integration_id', 'niveau'], 'circuit_type_niveau_unique');
            $table->index(['type_integration_id', 'actif', 'ordre'], 'circuit_type_actif_ordre_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circuit_validation_type_integration');
    }
};
