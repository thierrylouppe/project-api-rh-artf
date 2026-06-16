<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grille de référence : association classe ↔ grade ↔ coefficient
        // Optimisation : FKs vers les référentiels existants (categories + grades)
        // au lieu de dupliquer les libellés en string
        Schema::create('classegrillesalariales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')
                  ->constrained('categories')
                  ->restrictOnDelete();
            $table->foreignId('grade_id')
                  ->constrained('grades')
                  ->restrictOnDelete();
            $table->unsignedSmallInteger('coefficient')
                  ->comment('Coefficient de base de la classe (ex. 45, 50, …, 170)');
            $table->timestamps();

            $table->unique('categorie_id');
            $table->unique('grade_id');
        });

        // Paramètres globaux pilotant la génération (singleton — 1 seule ligne)
        Schema::create('parametregrilles', function (Blueprint $table) {
            $table->id();
            $table->decimal('valeur_point_indice', 10, 2)->default(300)
                  ->comment('Valeur monétaire du point d\'indice (FCFA)');
            $table->unsignedInteger('indice_base')->default(445)
                  ->comment('Indice de départ (Classe I, échelon 1)');
            $table->unsignedTinyInteger('echelon_depart')->default(1);
            $table->unsignedTinyInteger('echelon_fin')->default(12);
            $table->unsignedTinyInteger('ecart_depart')->default(45)
                  ->comment('Incrément de coefficient entre deux classes adjacentes');
            $table->timestamps();
        });

        // Grille générée : 10 classes × 12 échelons = 120 lignes
        Schema::create('salaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classegrillesalariale_id')
                  ->constrained('classegrillesalariales')
                  ->cascadeOnDelete();
            $table->unsignedTinyInteger('echelon');
            $table->unsignedInteger('indice');
            $table->decimal('salaire', 12, 2);
            $table->timestamps();

            $table->unique(['classegrillesalariale_id', 'echelon']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaires');
        Schema::dropIfExists('parametregrilles');
        Schema::dropIfExists('classegrillesalariales');
    }
};
