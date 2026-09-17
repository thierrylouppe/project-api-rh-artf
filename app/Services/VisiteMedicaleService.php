<?php

namespace App\Services;

use App\Enums\StatutAgent;
use App\Http\Resources\AgentIdentiteResource;
use App\Interfaces\AgentInterface;
use App\Interfaces\StructureSanitaireInterface;
use App\Interfaces\VisiteMedicaleInterface;
use App\Models\StructureSanitaire;
use App\Models\VisiteMedicale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property VisiteMedicaleInterface $repository */
class VisiteMedicaleService extends BaseService
{
    public function __construct(
        VisiteMedicaleInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly StructureSanitaireInterface $structureRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function alertesAnnuellesManquantes(?int $annee = null): array
    {
        $annee ??= (int) now()->year;
        $deja = $this->repository->idsAgentsAvecVisiteAnnuelle($annee);
        $presents = $this->agentRepository->getByStatuts(StatutAgent::effectifPresent());

        return $presents
            ->reject(fn ($agent) => in_array((int) $agent->id, $deja, true))
            ->values()
            ->map(fn ($agent) => (new AgentIdentiteResource($agent))->resolve())
            ->all();
    }

    protected function beforeCreate(array $data): array
    {
        $this->agentRepository->findById((int) $data['agent_id']);
        $structure = $this->structureRepository->findById((int) $data['structure_sanitaire_id']);
        abort_unless(
            $structure instanceof StructureSanitaire && $structure->actif,
            422,
            'Cette structure sanitaire n\'est plus agréée.'
        );
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $this->repository->findById($id);

        if (isset($data['structure_sanitaire_id'])) {
            $structure = $this->structureRepository->findById((int) $data['structure_sanitaire_id']);
            abort_unless(
                $structure instanceof StructureSanitaire && $structure->actif,
                422,
                'Cette structure sanitaire n\'est plus agréée.'
            );
        }

        return $data;
    }

    protected function afterCreate($model): VisiteMedicale
    {
        return $model->load(['agent:id,matricule,nom,prenom,statut', 'structure:id,nom,type']);
    }

    protected function afterUpdate($model): VisiteMedicale
    {
        return $this->afterCreate($model);
    }
}
