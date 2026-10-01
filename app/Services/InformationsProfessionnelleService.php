<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\InformationsProfessionnelleInterface;
use App\Models\InformationsProfessionnelle;

class InformationsProfessionnelleService extends BaseService
{
    public function __construct(
        InformationsProfessionnelleInterface $repository,
        private readonly AgentInterface $agentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): ?InformationsProfessionnelle
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->findByAgent($agentId);
    }

    public function upsert(int $agentId, array $data): InformationsProfessionnelle
    {
        $agent = $this->agentRepository->findById($agentId);
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');

        return $this->repository->upsertForAgent($agentId, $data);
    }
}
