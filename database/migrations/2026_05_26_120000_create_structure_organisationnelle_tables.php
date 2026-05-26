<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('localites', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('administrations', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('localite_id')->constrained('localites')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('directions', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('administration_id')->constrained('administrations')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('direction_id')->constrained('directions')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('bureaus', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bureaus');
        Schema::dropIfExists('services');
        Schema::dropIfExists('directions');
        Schema::dropIfExists('administrations');
        Schema::dropIfExists('localites');
    }
};
