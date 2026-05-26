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
            'consulter-agents', 'creer-agents', 'modifier-agents', 'supprimer-agents',
            // Recrutement
            'consulter-recrutement', 'creer-recrutement', 'valider-recrutement',
            // Contrats & carrière
            'consulter-contrats', 'creer-contrats', 'modifier-contrats',
            // Congés & absences
            'consulter-conges', 'creer-conges', 'valider-conges',
            'consulter-absences', 'creer-absences', 'valider-absences',
            // Évaluations
            'consulter-evaluations', 'creer-evaluations', 'valider-evaluations',
            // Reporting
            'consulter-reporting',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }
    }
}
