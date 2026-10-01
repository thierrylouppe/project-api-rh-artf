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

            // ── Vague F — rôles DRHL cloisonnés par bureau ──────────────────────────
            // Chaque rôle cumule les permissions de son bureau + la lecture globale de la structure.
            // Un utilisateur DRHL reçoit CE rôle en plus de (ou à la place de) 'rh'.

            // Bureau Personnel — gestion dossiers, carrière, discipline, congés
            'rh-personnel' => [
                'acces-bureau-personnel',
                'consulter-structure', 'consulter-referentiels',
                'consulter-agents', 'consulter-agents-global', 'creer-agents', 'modifier-agents',
                'consulter-recrutement', 'creer-recrutement', 'valider-recrutement',
                'consulter-contrats', 'creer-contrats', 'modifier-contrats',
                'consulter-nominations', 'gerer-nominations',
                'consulter-conges', 'valider-conges',
                'consulter-absences', 'valider-absences',
                'consulter-discipline', 'gerer-discipline', 'proposer-discipline',
                'consulter-evaluations',
                'consulter-utilisateurs', 'creer-utilisateurs', 'modifier-utilisateurs',
            ],

            // Bureau Solde — paie, salaires, éléments
            'rh-solde' => [
                'acces-bureau-solde',
                'consulter-structure', 'consulter-referentiels',
                'consulter-agents', 'consulter-agents-global',
                'consulter-salaires', 'gerer-salaires',
                'consulter-reporting',
            ],

            // Bureau Formation — catalogue, plans, inscriptions
            'rh-formation' => [
                'acces-bureau-formation',
                'consulter-structure', 'consulter-referentiels',
                'consulter-agents', 'consulter-agents-global',
                'consulter-formations', 'gerer-formations',
                'consulter-evaluations',
            ],

            // Bureau des Affaires Sociales — protection sociale, prestations
            'rh-affaires-sociales' => [
                'acces-bureau-affaires-sociales',
                'consulter-structure', 'consulter-referentiels',
                'consulter-agents', 'consulter-agents-global',
                'consulter-affaires-sociales', 'gerer-affaires-sociales', 'decider-prestations',
            ],

            // Bureau Étude et Planification — reporting, conformité, GPEEC
            'rh-etude' => [
                'acces-bureau-etude',
                'consulter-structure', 'consulter-referentiels',
                'consulter-agents', 'consulter-agents-global',
                'consulter-evaluations',
                'consulter-reporting',
            ],
            // ────────────────────────────────────────────────────────────────────────

            // Métier RH (DRHL) : utilisateurs, référentiels, agents, recrutement, contrats, salaires, reporting
            'rh' => [
                'consulter-utilisateurs', 'creer-utilisateurs', 'modifier-utilisateurs',
                'consulter-roles',
                'consulter-structure', 'consulter-referentiels', 'creer-referentiels', 'modifier-referentiels',
                'consulter-agents', 'consulter-agents-global', 'creer-agents', 'modifier-agents',
                'consulter-recrutement', 'creer-recrutement', 'valider-recrutement',
                'consulter-contrats', 'creer-contrats', 'modifier-contrats',
                'consulter-nominations', 'gerer-nominations',
                'consulter-salaires', 'gerer-salaires',
                'consulter-conges', 'creer-conges', 'valider-conges', 'consulter-absences', 'creer-absences', 'valider-absences',
                'consulter-discipline', 'gerer-discipline', 'proposer-discipline',
                'consulter-affaires-sociales', 'gerer-affaires-sociales',
                'consulter-formations', 'gerer-formations',
                // Évaluations : la DRHL ouvre les sessions et contrôle la conformité (CCN art. 66-70)
                'consulter-evaluations', 'creer-evaluations', 'valider-evaluations',
                'consulter-reporting',
            ],
            // Hiérarchie : lecture structure / agents + validations d'équipe (pas le métier RH)
            'directeur-general' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents', 'consulter-agents-global',
                'consulter-nominations',
                'consulter-salaires',
                'consulter-conges', 'creer-conges', 'valider-conges',
                'consulter-absences', 'creer-absences', 'valider-absences',
                'consulter-discipline', 'prononcer-discipline',
                'consulter-affaires-sociales', 'decider-prestations',
                'consulter-formations',
                'consulter-evaluations', 'valider-evaluations',
                'consulter-reporting',
            ],
            'directeur' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-conges', 'creer-conges', 'valider-conges',
                'consulter-absences', 'creer-absences', 'valider-absences',
                'proposer-discipline',
                'consulter-evaluations', 'valider-evaluations',
            ],
            // Notateurs au sens CCN art. 64 : ils notent et signent les fiches de leur équipe
            'chef-service' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-conges', 'creer-conges', 'valider-conges',
                'consulter-absences', 'creer-absences', 'valider-absences',
                'proposer-discipline',
                'consulter-evaluations', 'valider-evaluations',
            ],
            'chef-bureau' => [
                'consulter-structure', 'consulter-referentiels', 'consulter-agents',
                'consulter-nominations',
                'consulter-conges', 'creer-conges', 'valider-conges',
                'consulter-absences', 'creer-absences', 'valider-absences',
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
