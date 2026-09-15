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
                'consulter-nominations', 'gerer-nominations',
                'consulter-salaires', 'gerer-salaires',
                'consulter-conges', 'creer-conges', 'valider-conges', 'consulter-absences', 'creer-absences', 'valider-absences',
                'consulter-discipline', 'gerer-discipline', 'proposer-discipline',
                // Évaluations : la DRHL ouvre les sessions et contrôle la conformité (CCN art. 66-70)
                'consulter-evaluations', 'creer-evaluations', 'valider-evaluations',
                'consulter-reporting',
            ],
            // Hiérarchie : lecture structure / agents + validations d'équipe (pas le métier RH)
            'directeur-general' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-salaires',
                'consulter-conges', 'valider-conges',
                'consulter-absences', 'valider-absences',
                'consulter-discipline', 'prononcer-discipline',
                'consulter-evaluations', 'valider-evaluations',
            ],
            'directeur' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-conges', 'valider-conges',
                'consulter-absences', 'valider-absences',
                'proposer-discipline',
                'consulter-evaluations', 'valider-evaluations',
            ],
            // Notateurs au sens CCN art. 64 : ils notent et signent les fiches de leur équipe
            'chef-service' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-conges', 'valider-conges', 'consulter-absences', 'valider-absences',
                'proposer-discipline',
                'consulter-evaluations', 'valider-evaluations',
            ],
            'chef-bureau' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-conges', 'valider-conges', 'consulter-absences', 'valider-absences',
                'proposer-discipline',
                'consulter-evaluations', 'valider-evaluations',
            ],
            // L'agent consulte sa fiche, la signe (art. 63) et peut réclamer (art. 65)
            'agent' => [
                'consulter-referentiels', 'consulter-conges', 'creer-conges',
                'consulter-absences', 'creer-absences',
                'consulter-evaluations',
            ],
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
            $role->syncPermissions($permissions);
        }
    }
}
