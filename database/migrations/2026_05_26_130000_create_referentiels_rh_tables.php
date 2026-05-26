<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diplomes', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('niveau')->default(1)->comment('Niveau hiérarchique du grade');
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('echelons', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->unsignedTinyInteger('numero');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('fonctions', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('type_contrats', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->string('sigle')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('type_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->boolean('obligatoire')->default(false);
            $table->timestamps();
        });

        Schema::create('type_recrutements', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('type_absences', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->boolean('justification_requise')->default(true);
            $table->timestamps();
        });

        Schema::create('type_conges', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('jours_max')->default(0)->comment('0 = illimité');
            $table->timestamps();
        });

        Schema::create('motifs_administratifs', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motifs_administratifs');
        Schema::dropIfExists('type_conges');
        Schema::dropIfExists('type_absences');
        Schema::dropIfExists('type_recrutements');
        Schema::dropIfExists('type_documents');
        Schema::dropIfExists('type_contrats');
        Schema::dropIfExists('fonctions');
        Schema::dropIfExists('echelons');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('grades');
        Schema::dropIfExists('diplomes');
    }
};
