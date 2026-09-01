<?php

namespace App\Services;

use App\Interfaces\PriseDeServiceInterface;
use App\Interfaces\UserInterface;
use App\Models\PriseDeService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PriseDeServiceService extends BaseService
{
    public function __construct(
        PriseDeServiceInterface $repository,
        private readonly NotificationService $notificationService,
        private readonly UserInterface $userRepository,
    ) {
        parent::__construct($repository);
    }

    public function findByAgent(int $agentId): ?PriseDeService
    {
        return $this->repository->findByAgent($agentId);
    }

    protected function afterCreate(Model $model): Model
    {
        $destinataires = collect();
        $compteAgent   = $this->userRepository->findByAgentId((int) $model->agent_id);

        if ($compteAgent instanceof User) {
            $destinataires->push($compteAgent);
        }

        if ($model->dossier_integration_id) {
            $model->loadMissing('dossier');
            if ($model->dossier?->demandeur_id) {
                $demandeur = $this->userRepository->findOptional((int) $model->dossier->demandeur_id);
                if ($demandeur instanceof User) {
                    $destinataires->push($demandeur);
                }
            }
        }

        $this->notificationService->notifierEvenementGroupe(
            $destinataires,
            'prise_de_service',
            'confirmee',
            'La prise de service a été confirmée.',
            [
                'prise_de_service_id' => $model->id,
                'agent_id'            => $model->agent_id,
                'dossier_id'          => $model->dossier_integration_id,
            ]
        );

        return $model;
    }
}
