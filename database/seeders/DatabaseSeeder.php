<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordre d'exécution — respecter les chaînes de dépendances :
     *
     * Auth          : Permission → Role → (User plus bas)
     * Structure     : Localite → Administration → Direction → Service → Bureau
     * Grille        : Grade + Categorie → Classegrillesalariale → Diplome
     *               (+ Echelon → Parametregrile, cohérence sans FK)
     * Intégration   : TypeDocument → TypeIntegration → CircuitValidation
     */
    public function run(): void
    {
        $this->call([
            // ── 1. Auth Spatie ──────────────────────────────────────────
            PermissionSeeder::class,
            RoleSeeder::class,

            // ── 2. Structure organisationnelle ──────────────────────────
            LocaliteSeeder::class,
            AdministrationSeeder::class,
            DirectionSeeder::class,
            ServiceSeeder::class,
            BureauSeeder::class,

            // ── 3. Grille salariale & carrière ──────────────────────────
            GradeSeeder::class,
            CategorieSeeder::class,
            EchelonSeeder::class,
            ClassegrillesalarialeSeeder::class, // depends: Grade, Categorie
            DiplomeSeeder::class,               // depends: Classegrillesalariale
            ParametregrileSeeder::class,        // cohérent avec Echelon (1–12)

            // ── 4. Référentiels RH indépendants ─────────────────────────
            FonctionSeeder::class,
            TypeContratSeeder::class,
            TypeAbsenceSeeder::class,
            TypeCongeSeeder::class,
            MotifAdministratifSeeder::class,

            // ── 5. Recrutement / intégration ────────────────────────────
            TypeDocumentSeeder::class,
            TypeIntegrationSeeder::class,  // depends: TypeDocument
            CircuitValidationSeeder::class, // depends: TypeIntegration

            // ── 6. Utilisateurs & paramètres applicatifs ────────────────
            UserSeeder::class,                  // depends: Role
            ParametreApplicationSeeder::class,
        ]);
    }
}
