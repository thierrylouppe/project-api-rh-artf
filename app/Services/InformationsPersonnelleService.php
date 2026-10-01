<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\InformationsPersonnelleInterface;
use App\Models\InformationsPersonnelle;

class InformationsPersonnelleService extends BaseService
{
    public function __construct(
        InformationsPersonnelleInterface $repository,
        private readonly AgentInterface $agentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): ?InformationsPersonnelle
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->findByAgent($agentId);
    }

    public function upsert(int $agentId, array $data): InformationsPersonnelle
    {
        $this->assertModifiable($agentId);

        return $this->repository->upsertForAgent($agentId, $data);
    }

    private function assertModifiable(int $agentId): void
    {
        $agent = $this->agentRepository->findById($agentId);
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');
    }
}
