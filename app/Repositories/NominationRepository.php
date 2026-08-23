<?php

namespace App\Repositories;

use App\Enums\StatutNomination;
use App\Interfaces\NominationInterface;
use App\Models\Nomination;
use Illuminate\Support\Collection;

class NominationRepository extends BaseRepository implements NominationInterface
{
    protected function model(): string
    {
        return Nomination::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return Nomination::query()
            ->where('agent_id', $agentId)
            ->with('structure')
            ->orderByDesc('date_debut')
            ->orderByDesc('id')
            ->get();
    }

    public function getActive(int $agentId): ?Nomination
    {
        return Nomination::query()
            ->where('agent_id', $agentId)
            ->where('statut', StatutNomination::ACTIVE)
            ->latest('id')
            ->first();
    }

    public function cloturer(int $id, ?string $dateFin): Nomination
    {
        $nomination = $this->findById($id);
        $nomination->update([
            'statut'   => StatutNomination::CLOTUREE,
            'date_fin' => $dateFin ?? now()->toDateString(),
        ]);

        return $nomination->fresh();
    }

    public function cloturerActivesPourStructure(string $structurableType, int $structurableId, ?int $saufId = null): void
    {
        Nomination::query()
            ->where('structurable_type', $structurableType)
            ->where('structurable_id', $structurableId)
            ->where('statut', StatutNomination::ACTIVE)
            ->when($saufId !== null, fn ($q) => $q->where('id', '!=', $saufId))
            ->update([
                'statut'   => StatutNomination::CLOTUREE,
                'date_fin' => now()->toDateString(),
            ]);
    }

    public function cloturerActivePourAgent(int $agentId, ?int $saufId = null): void
    {
        Nomination::query()
            ->where('agent_id', $agentId)
            ->where('statut', StatutNomination::ACTIVE)
            ->when($saufId !== null, fn ($q) => $q->where('id', '!=', $saufId))
            ->update([
                'statut'   => StatutNomination::CLOTUREE,
                'date_fin' => now()->toDateString(),
            ]);
    }
}
