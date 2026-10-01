<?php

namespace App\Services;

use App\Enums\StatutAgent;
use App\Interfaces\AgentInterface;
use App\Interfaces\AvertissementInterface;
use App\Models\Avertissement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property AvertissementInterface $repository */
class AvertissementService extends BaseService
{
    public function __construct(
        AvertissementInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly NotificationService $notificationService,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function mesAvertissements(): Collection
    {
        return $this->repository->getByAgent($this->agentConnecteId());
    }

    public function monAvertissement(int $id): Avertissement
    {
        $avertissement = $this->repository->findById($id);

        abort_unless(
            (int) $avertissement->agent_id === $this->agentConnecteId(),
            404,
            'Avertissement introuvable.'
        );

        return $avertissement->load(['agent:id,matricule,nom,prenom', 'emetteur:id,name']);
    }

    private function agentConnecteId(): int
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');
        abort_unless($user->agent_id, 403, 'Aucun agent associé à ce compte utilisateur.');

        return (int) $user->agent_id;
    }

    public function create(array $data): Avertissement
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        abort_if(
            $agent->archived_at !== null || $agent->statut === StatutAgent::ARCHIVE->value,
            422,
            'Impossible d\'émettre un avertissement pour un agent archivé.'
        );

        $data['emetteur_id'] = $data['emetteur_id'] ?? Auth::id();

        $avertissement = $this->repository->create($data);
        $avertissement->load(['agent:id,matricule,nom,prenom', 'emetteur:id,name']);

        $this->notifier($avertissement, 'avertissement_cree', 'Un avertissement a été émis.');

        return $avertissement;
    }

    public function update(int $id, array $data): Avertissement
    {
        if (isset($data['agent_id'])) {
            $this->agentRepository->findById((int) $data['agent_id']);
        }

        unset($data['emetteur_id']);

        return $this->repository->update($id, $data)->load(['agent:id,matricule,nom,prenom', 'emetteur:id,name']);
    }

    private function notifier(Avertissement $avertissement, string $action, string $message): void
    {
        $meta = [
            'avertissement_id' => $avertissement->id,
            'agent_id' => $avertissement->agent_id,
        ];

        $this->notificationService->notifierEvenementGroupe(
            $this->notificationService->destinatairesRoleEtAgent(
                'rh',
                (int) $avertissement->agent_id,
                Auth::id()
            ),
            'discipline',
            $action,
            $message,
            $meta
        );
    }
}
