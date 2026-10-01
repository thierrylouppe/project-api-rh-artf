<?php

namespace App\Repositories;

use App\Interfaces\CertificationFormationInterface;
use App\Models\CertificationFormation;
use Illuminate\Support\Collection;

class CertificationFormationRepository extends BaseRepository implements CertificationFormationInterface
{
    protected function model(): string
    {
        return CertificationFormation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = CertificationFormation::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut',
                'formation',
                'diplome',
                'uploader:id,name',
            ]);

        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }
        if (isset($filters['formation_id'])) {
            $query->where('formation_id', $filters['formation_id']);
        }

        return $query->orderByDesc('date_obtention')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->getAll(['agent_id' => $agentId]);
    }
}
