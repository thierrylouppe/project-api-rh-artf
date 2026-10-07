<?php

namespace App\Services;

use App\Enums\StatutAgent;
use App\Enums\StatutCampagneCongeAnnuel;
use App\Interfaces\AgentInterface;
use App\Interfaces\CampagneCongeAnnuelInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Models\CampagneCongeAnnuel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property CampagneCongeAnnuelInterface $repository */
class CampagneCongeAnnuelService extends BaseService
{
    public function __construct(
        CampagneCongeAnnuelInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly DemandeCongeInterface $demandeRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $this->assertRh();
        abort_if(
            $this->repository->findByAnnee((int) $data['annee']) !== null,
            422,
            "Une campagne de congé annuel existe déjà pour {$data['annee']}."
        );

        $data['statut']     = StatutCampagneCongeAnnuel::BROUILLON;
        $data['created_by'] = Auth::id();

        return $data;
    }

    public function ouvrir(int $id): CampagneCongeAnnuel
    {
        $this->assertRh();
        $campagne = $this->campagne($id);

        abort_unless(
            $campagne->statut === StatutCampagneCongeAnnuel::BROUILLON,
            422,
            'Seule une campagne en brouillon peut être ouverte.'
        );
        abort_if(
            $this->repository->findOuverte() !== null,
            422,
            'Une campagne de congé annuel est déjà ouverte.'
        );

        /** @var CampagneCongeAnnuel $ouverte */
        $ouverte = $this->repository->update($id, [
            'statut' => StatutCampagneCongeAnnuel::OUVERTE,
        ]);

        return $ouverte;
    }

    public function cloturer(int $id): CampagneCongeAnnuel
    {
        $this->assertRh();
        $campagne = $this->campagne($id);

        abort_unless(
            $campagne->statut === StatutCampagneCongeAnnuel::OUVERTE,
            422,
            'Seule une campagne ouverte peut être clôturée.'
        );

        /** @var CampagneCongeAnnuel $close */
        $close = $this->repository->update($id, [
            'statut'                 => StatutCampagneCongeAnnuel::CLOTUREE,
            'date_cloture_effective' => now(),
        ]);

        return $close;
    }

    public function sansProposition(int $id): Collection
    {
        $this->assertRh();
        $this->campagne($id);

        $deja = $this->demandeRepository->agentIdsPourCampagne($id);

        return $this->agentRepository
            ->getByStatut(StatutAgent::ACTIF->value)
            ->reject(fn ($agent) => $deja->contains($agent->id))
            ->values();
    }

    private function campagne(int $id): CampagneCongeAnnuel
    {
        /** @var CampagneCongeAnnuel $campagne */
        $campagne = $this->repository->findById($id);

        return $campagne;
    }

    private function assertRh(): void
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');
        abort_unless(
            $user->hasRole('rh') || $user->hasRole('admin'),
            403,
            'Seuls les RH (DRHL) peuvent gérer la campagne de congé annuel.'
        );
    }
}
