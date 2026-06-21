<?php

namespace App\Repositories;

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
        return Nomination::where('agent_id', $agentId)->get();
    }

    public function getActive(int $agentId): ?Nomination
    {
        return Nomination::where('agent_id', $agentId)
            ->where('statut', 'active')
            ->latest()
            ->first();
    }

    public function cloturerNominationsActives(string $structurableType, int $structurableId): void
    {
        Nomination::where('structurable_type', $structurableType)
            ->where('structurable_id', $structurableId)
            ->where('statut', 'active')
            ->update([
                'statut'   => 'cloturee',
                'date_fin' => now()->toDateString(),
            ]);
    }
}
