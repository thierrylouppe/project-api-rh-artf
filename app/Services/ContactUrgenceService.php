<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\ContactUrgenceInterface;
use App\Models\ContactUrgence;
use Illuminate\Support\Collection;

class ContactUrgenceService extends BaseService
{
    public function __construct(
        ContactUrgenceInterface $repository,
        private readonly AgentInterface $agentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function createForAgent(int $agentId, array $data): ContactUrgence
    {
        $this->assertModifiable($agentId);
        $data['agent_id'] = $agentId;

        return $this->repository->create($data);
    }

    public function updateForAgent(int $agentId, int $id, array $data): ContactUrgence
    {
        $this->assertModifiable($agentId);
        $contact = $this->charger($agentId, $id);

        return $this->repository->update($contact->id, $data);
    }

    public function deleteForAgent(int $agentId, int $id): void
    {
        $this->assertModifiable($agentId);
        $contact = $this->charger($agentId, $id);
        $this->repository->delete($contact->id);
    }

    private function charger(int $agentId, int $id): ContactUrgence
    {
        $contact = $this->repository->findById($id);
        abort_unless((int) $contact->agent_id === $agentId, 404, 'ContactUrgence introuvable.');

        return $contact;
    }

    private function assertModifiable(int $agentId): void
    {
        $agent = $this->agentRepository->findById($agentId);
        abort_if($agent->statut === 'archive', 422, 'Cet agent est archivé : dossier en lecture seule.');
    }
}
