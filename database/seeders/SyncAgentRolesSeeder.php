<?php

namespace Database\Seeders;

use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Attribue à chaque compte agent :
 *  - le rôle de sa fonction (DG, directeur, CS, CB, agent)
 *  - le rôle métier RH uniquement si Directeur DRHL (`rh`)
 *  - le rôle de bureau DRHL (`rh-formation`, `rh-solde`…) si affecté à ce bureau
 *  - le `bureau_id` de cloisonnement dérivé de l'affectation active
 *
 * Vue globale (bureau_id null) : DG et Directeur DRHL.
 * Les comptes système (admin@, rh@, agent@, …) ne sont pas touchés.
 */
class SyncAgentRolesSeeder extends Seeder
{
    public const ROLES_DRHL = [
        'B.P'    => 'rh-personnel',
        'B.F'    => 'rh-formation',
        'B.S.'   => 'rh-solde',
        'B.A.S.' => 'rh-affaires-sociales',
        'B.PL'   => 'rh-etude',
    ];

    public static function rolesPour(
        ?string $fonctionSigle,
        ?string $bureauSigle = null,
        ?string $directionSigle = null,
        bool $affecteAuBureau = false,
    ): array {
        $roleFonction = match ($fonctionSigle) {
            'DG'        => 'directeur-general',
            'DC', 'DD'  => 'directeur',
            'CS', 'CSR' => 'chef-service',
            'CB'        => 'chef-bureau',
            default     => 'agent',
        };

        $roles = [$roleFonction];

        if ($roleFonction === 'directeur' && $directionSigle === 'D.R.H.L') {
            $roles[] = 'rh';
        }

        if ($affecteAuBureau && $bureauSigle && isset(self::ROLES_DRHL[$bureauSigle])) {
            $roles[] = self::ROLES_DRHL[$bureauSigle];
        }

        return array_values(array_unique($roles));
    }

    public static function attribuer(User $user): void
    {
        $agent = $user->agent;
        if (! $agent) {
            return;
        }

        $agent->loadMissing(['fonction', 'affectationActive']);

        $fonctionSigle   = $agent->fonction?->sigle;
        $bureauId        = null;
        $bureauSigle     = null;
        $directionSigle  = null;
        $affecteAuBureau = false;

        $aff = $agent->affectationActive;
        if ($aff) {
            $type = $aff->structurable_type;
            $id   = (int) $aff->structurable_id;

            if (is_a($type, Bureau::class, true)) {
                $bureau = Bureau::with('service.direction')->find($id);
                $bureauId        = $bureau?->id;
                $bureauSigle     = $bureau?->sigle;
                $directionSigle  = $bureau?->service?->direction?->sigle;
                $affecteAuBureau = true;
            } elseif (is_a($type, Service::class, true)) {
                $service = Service::with('direction')->find($id);
                $directionSigle = $service?->direction?->sigle;
                $bureauId       = Bureau::where('service_id', $id)->orderBy('id')->value('id');
                $bureauSigle    = $bureauId ? Bureau::find($bureauId)?->sigle : null;
            } elseif (is_a($type, Direction::class, true)) {
                $directionSigle = Direction::find($id)?->sigle;
                $serviceIds     = Service::where('direction_id', $id)->pluck('id');
                $bureauId       = Bureau::whereIn('service_id', $serviceIds)->orderBy('id')->value('id');
            }
        }

        $vueGlobale = $fonctionSigle === 'DG'
            || ($directionSigle === 'D.R.H.L' && in_array($fonctionSigle, ['DC', 'DD'], true));

        if ($vueGlobale) {
            $bureauId        = null;
            $bureauSigle     = null;
            $affecteAuBureau = false;
        }

        $user->update(['bureau_id' => $bureauId]);
        $user->syncRoles(self::rolesPour(
            $fonctionSigle,
            $bureauSigle,
            $directionSigle,
            $affecteAuBureau,
        ));
    }

    public function run(): void
    {
        $users = User::query()
            ->with(['agent.fonction', 'agent.affectationActive', 'bureau'])
            ->whereNotNull('agent_id')
            ->get();

        foreach ($users as $user) {
            self::attribuer($user);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info($users->count().' comptes agents : rôles et périmètre d\'affectation.');
    }
}
