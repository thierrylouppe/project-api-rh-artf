<?php

namespace App\Repositories;

use App\Enums\StatutInscriptionFormation;
use App\Interfaces\InscriptionFormationInterface;
use App\Models\InscriptionFormation;
use Illuminate\Support\Collection;

class InscriptionFormationRepository extends BaseRepository implements InscriptionFormationInterface
{
    protected function model(): string
    {
        return InscriptionFormation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = InscriptionFormation::query()
            ->with([
                'agent:id,matricule,nom,prenom,statut,date_prise_service',
                'formation',
                'plan',
            ]);

        if (method_exists(InscriptionFormation::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('date_inscription')->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return InscriptionFormation::query()
            ->where('agent_id', $agentId)
            ->with(['formation', 'plan'])
            ->orderByDesc('date_inscription')
            ->get();
    }

    public function findOuverte(int $agentId, int $formationId, ?int $excludeId = null): ?InscriptionFormation
    {
        return InscriptionFormation::query()
            ->where('agent_id', $agentId)
            ->where('formation_id', $formationId)
            ->whereIn('statut', [
                StatutInscriptionFormation::INSCRITE,
                StatutInscriptionFormation::PRESENTE,
            ])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->first();
    }
}
