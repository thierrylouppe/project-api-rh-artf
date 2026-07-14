<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Module 1.3 — Administration système (en premier)
            PermissionSeeder::class,
            RoleSeeder::class,
            // Module 1.1 — Structure organisationnelle
            LocaliteSeeder::class,
            AdministrationSeeder::class,
            DirectionSeeder::class,
            ServiceSeeder::class,
            BureauSeeder::class,
            // Module 1.2 — Référentiels RH
            // Ordre grille : Grade + Categorie → Classegrillesalariale → Diplome
            GradeSeeder::class,
            CategorieSeeder::class,
            EchelonSeeder::class,
            ClassegrillesalarialeSeeder::class,
            DiplomeSeeder::class,
            ParametregrileSeeder::class,
            FonctionSeeder::class,
            TypeContratSeeder::class,
            TypeDocumentSeeder::class,
            TypeIntegrationSeeder::class,
            CircuitValidationSeeder::class,
            TypeAbsenceSeeder::class,
            TypeCongeSeeder::class,
            MotifAdministratifSeeder::class,
            // Module 1.3 — Utilisateurs & paramètres
            UserSeeder::class,
            ParametreApplicationSeeder::class,
        ]);
    }
}
