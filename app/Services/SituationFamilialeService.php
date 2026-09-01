<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\SituationFamilialeInterface;
use App\Models\SituationFamiliale;

class SituationFamilialeService extends BaseService
{
    public function __construct(
        SituationFamilialeInterface $repository,
        private readonly AgentInterface $agentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): ?SituationFamiliale
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->findByAgent($agentId);
    }

    public function upsert(int $agentId, array $data): SituationFamiliale
    {
        $agent = $this->agentRepository->findById($agentId);
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');

        return $this->repository->upsertForAgent($agentId, $data);
    }
}
