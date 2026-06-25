<?php

namespace App\Services;

use App\Interfaces\ConventionStageInterface;
use App\Models\ConventionStage;
use Illuminate\Support\Collection;

/** @property ConventionStageInterface $repository */
class ConventionStageService extends BaseService
{
    public function __construct(ConventionStageInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getAll(array $filters = []): Collection
    {
        return $this->repository->getAll($filters);
    }

    /**
     * Prolonge la date de fin d'une convention en cours.
     * La nouvelle date doit être postérieure à l'actuelle.
     */
    public function prolonger(int $id, string $nouvelleDateFin): ConventionStage
    {
        $convention = $this->repository->findById($id);

        abort_unless(
            $convention->statut_stage->estActif(),
            422,
            'Seule une convention EN_COURS peut être prolongée'
        );

        abort_unless(
            $nouvelleDateFin > $convention->date_fin->format('Y-m-d'),
            422,
            'La nouvelle date de fin doit être postérieure à la date actuelle'
        );

        return $this->repository->update($id, ['date_fin' => $nouvelleDateFin]);
    }

    public function findByDossier(int $dossierId): ?ConventionStage
    {
        return $this->repository->findByDossier($dossierId);
    }
}
