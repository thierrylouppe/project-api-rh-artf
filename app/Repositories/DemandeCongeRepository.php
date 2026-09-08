<?php

namespace App\Repositories;

use App\Enums\StatutDemandeConge;
use App\Interfaces\DemandeCongeInterface;
use App\Models\DemandeConge;
use Illuminate\Support\Collection;

class DemandeCongeRepository extends BaseRepository implements DemandeCongeInterface
{
    protected function model(): string
    {
        return DemandeConge::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = DemandeConge::query()->with(['agent', 'typeConge']);

        if (method_exists(DemandeConge::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return DemandeConge::query()
            ->where('agent_id', $agentId)
            ->with(['typeConge', 'agent'])
            ->orderByDesc('date_debut')
            ->get();
    }

    public function getEnAttenteValidation(): Collection
    {
        return DemandeConge::query()
            ->whereIn('statut', [
                StatutDemandeConge::SOUMISE->value,
                StatutDemandeConge::VALIDEE_N1->value,
                StatutDemandeConge::VALIDEE_RH->value,
            ])
            ->with(['typeConge', 'agent'])
            ->orderBy('date_debut')
            ->get();
    }

    public function chevauchements(int $agentId, string $debut, string $fin, ?int $exclureId = null): Collection
    {
        $ouverts = [
            StatutDemandeConge::SOUMISE->value,
            StatutDemandeConge::VALIDEE_N1->value,
            StatutDemandeConge::VALIDEE_RH->value,
            StatutDemandeConge::VALIDEE_DG->value,
        ];

        $query = DemandeConge::query()
            ->where('agent_id', $agentId)
            ->whereIn('statut', $ouverts)
            ->whereDate('date_debut', '<=', $fin)
            ->whereDate('date_fin', '>=', $debut);

        if ($exclureId !== null) {
            $query->where('id', '!=', $exclureId);
        }

        return $query->get();
    }
}
