<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Administration système
            'consulter-utilisateurs', 'creer-utilisateurs', 'modifier-utilisateurs', 'supprimer-utilisateurs',
            'consulter-roles', 'creer-roles', 'modifier-roles', 'supprimer-roles',
            // Structure organisationnelle
            'consulter-structure', 'creer-structure', 'modifier-structure', 'supprimer-structure',
            // Référentiels RH
            'consulter-referentiels', 'creer-referentiels', 'modifier-referentiels', 'supprimer-referentiels',
            // Agents
            'consulter-agents', 'consulter-agents-global', 'creer-agents', 'modifier-agents', 'supprimer-agents',
            // Recrutement
            'consulter-recrutement', 'creer-recrutement', 'valider-recrutement',
            // Contrats & carrière
            'consulter-contrats', 'creer-contrats', 'modifier-contrats',
            'consulter-nominations', 'gerer-nominations',
            // Salaires & grille
            'consulter-salaires', 'gerer-salaires',
            // Congés & absences
            'consulter-conges', 'creer-conges', 'valider-conges',
            'consulter-absences', 'creer-absences', 'valider-absences',
            // Discipline
            'consulter-discipline', 'gerer-discipline', 'proposer-discipline', 'prononcer-discipline',
            // Affaires sociales
            'consulter-affaires-sociales', 'gerer-affaires-sociales', 'decider-prestations',
            // Formations
            'consulter-formations', 'gerer-formations',
            // Évaluations
            'consulter-evaluations', 'creer-evaluations', 'valider-evaluations',
            // Reporting
            'consulter-reporting',

            // Vague F — permissions fines par bureau DRHL
            // B.P  — Bureau Personnel
            'acces-bureau-personnel',
            // B.S. — Bureau Solde
            'acces-bureau-solde',
            // B.F  — Bureau Formation
            'acces-bureau-formation',
            // B.A.S. — Bureau des Affaires Sociales
            'acces-bureau-affaires-sociales',
            // B.PL — Bureau Étude et Planification
            'acces-bureau-etude',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
    }
}
