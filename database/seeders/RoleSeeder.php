<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $all = Permission::where('guard_name', 'api')->pluck('name')->toArray();

        $roles = [
            'admin' => $all,
            // Métier RH (DRHL) : utilisateurs, référentiels, agents, recrutement, contrats, salaires, reporting
            'rh' => [
                'consulter-utilisateurs', 'creer-utilisateurs', 'modifier-utilisateurs',
                'consulter-structure', 'consulter-referentiels', 'creer-referentiels', 'modifier-referentiels',
                'consulter-agents', 'creer-agents', 'modifier-agents',
                'consulter-recrutement', 'creer-recrutement', 'valider-recrutement',
                'consulter-contrats', 'creer-contrats', 'modifier-contrats',
                'consulter-salaires', 'gerer-salaires',
                'consulter-conges', 'valider-conges', 'consulter-absences', 'valider-absences',
                'consulter-evaluations', 'consulter-reporting',
            ],
            // Hiérarchie : lecture structure / agents + validations d'équipe (pas le métier RH)
            'directeur-general' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-conges', 'valider-conges',
                'consulter-absences', 'valider-absences',
                'consulter-evaluations', 'valider-evaluations',
            ],
            'directeur' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-conges', 'valider-conges',
                'consulter-absences', 'valider-absences',
                'consulter-evaluations', 'valider-evaluations',
            ],
            'chef-service' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-conges', 'valider-conges', 'consulter-absences',
            ],
            'chef-bureau' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-conges', 'valider-conges', 'consulter-absences',
            ],
            'agent' => [
                'consulter-referentiels', 'consulter-conges', 'creer-conges',
                'consulter-absences', 'creer-absences',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
            $role->syncPermissions($permissions);
        }
    }
}
