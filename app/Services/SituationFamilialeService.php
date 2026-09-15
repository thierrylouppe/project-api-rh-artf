<?php

namespace App\Services;

use App\Enums\TypeAyantDroit;
use App\Interfaces\AgentInterface;
use App\Interfaces\AyantDroitInterface;
use App\Interfaces\SituationFamilialeInterface;
use App\Models\AyantDroit;
use App\Models\SituationFamiliale;

class SituationFamilialeService extends BaseService
{
    public function __construct(
        SituationFamilialeInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly AyantDroitInterface $ayantDroitRepository,
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

        if ($this->ayantDroitRepository->existsEnfantForAgent($agentId)) {
            unset($data['nb_enfants']);
            $this->repository->upsertForAgent($agentId, $data);
            $nb = $this->ayantDroitRepository->getByAgent($agentId)
                ->filter(fn (AyantDroit $item) => $item->type === TypeAyantDroit::ENFANT && $item->estACharge())
                ->count();

            return $this->repository->upsertForAgent($agentId, ['nb_enfants' => $nb]);
        }

        return $this->repository->upsertForAgent($agentId, $data);
    }
}
