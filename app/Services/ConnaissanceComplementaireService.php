<?php

namespace App\Services;

use App\Interfaces\ConnaissanceComplementaireInterface;
use App\Models\ConnaissanceComplementaire;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Connaissances complémentaires / besoins de formation (Phase 5.3).
 * Identifiés pendant l'évaluation de l'agent.
 */
class ConnaissanceComplementaireService extends BaseService
{
    public function __construct(ConnaissanceComplementaireInterface $repository)
    {
        parent::__construct($repository);
    }

    public function parEvaluation(int $evaluationId): Collection
    {
        return $this->repository->parEvaluation($evaluationId);
    }

    public function ajouter(int $evaluationId, array $data, User $user): ConnaissanceComplementaire
    {
        return $this->repository->create([
            'evaluation_id' => $evaluationId,
            'type'          => $data['type'] ?? 'formation',
            'domaine'       => $data['domaine'],
            'description'   => $data['description'] ?? null,
            'urgent'        => (bool) ($data['urgent'] ?? false),
            'created_by'    => $user->id,
        ]);
    }
}
