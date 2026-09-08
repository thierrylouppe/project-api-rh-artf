<?php

namespace App\Repositories;

use App\Enums\StatutAbsence;
use App\Interfaces\AbsenceInterface;
use App\Models\Absence;
use Illuminate\Support\Collection;

class AbsenceRepository extends BaseRepository implements AbsenceInterface
{
    protected function model(): string
    {
        return Absence::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = Absence::query()->with(['agent', 'typeAbsence']);

        if (method_exists(Absence::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->get();
    }

    public function getByAgent(int $agentId): Collection
    {
        return Absence::query()
            ->where('agent_id', $agentId)
            ->with(['typeAbsence', 'agent'])
            ->orderByDesc('date_debut')
            ->get();
    }

    public function getEnAttente(): Collection
    {
        return Absence::query()
            ->where('statut', StatutAbsence::EN_ATTENTE)
            ->with(['agent', 'typeAbsence'])
            ->orderBy('date_debut')
            ->get();
    }

    public function chevauchements(int $agentId, string $debut, string $fin, ?int $exclureId = null): Collection
    {
        $query = Absence::query()
            ->where('agent_id', $agentId)
            ->whereIn('statut', [
                StatutAbsence::EN_ATTENTE->value,
                StatutAbsence::VALIDEE->value,
            ])
            ->whereDate('date_debut', '<=', $fin)
            ->whereDate('date_fin', '>=', $debut);

        if ($exclureId !== null) {
            $query->where('id', '!=', $exclureId);
        }

        return $query->get();
    }
}
