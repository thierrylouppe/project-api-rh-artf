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
            'rh' => [
                'consulter-utilisateurs', 'creer-utilisateurs', 'modifier-utilisateurs',
                'consulter-structure', 'consulter-referentiels', 'creer-referentiels', 'modifier-referentiels',
                'consulter-agents', 'creer-agents', 'modifier-agents',
                'consulter-recrutement', 'creer-recrutement', 'valider-recrutement',
                'consulter-contrats', 'creer-contrats', 'modifier-contrats',
                'consulter-conges', 'valider-conges', 'consulter-absences', 'valider-absences',
                'consulter-evaluations', 'consulter-reporting',
            ],
            'directeur' => [
                'consulter-utilisateurs', 'consulter-structure', 'consulter-referentiels',
                'consulter-agents', 'consulter-recrutement', 'valider-recrutement',
                'consulter-contrats', 'consulter-conges', 'valider-conges',
                'consulter-absences', 'valider-absences', 'consulter-evaluations', 'valider-evaluations',
                'consulter-reporting',
            ],
            'chef-service' => [
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
