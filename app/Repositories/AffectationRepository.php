<?php

namespace App\Repositories;

use App\Interfaces\AffectationInterface;
use App\Models\Affectation;
use Illuminate\Support\Collection;

class AffectationRepository extends BaseRepository implements AffectationInterface
{
    protected function model(): string
    {
        return Affectation::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return Affectation::where('agent_id', $agentId)->get();
    }

    public function getActive(int $agentId): ?Affectation
    {
        return Affectation::where('agent_id', $agentId)
            ->where('statut', 'active')
            ->latest()
            ->first();
    }

    public function terminer(int $id, ?string $dateFin): Affectation
    {
        $affectation = $this->findById($id);
        $affectation->update([
            'statut'   => 'terminee',
            'date_fin' => $dateFin ?? now()->toDateString(),
        ]);

        return $affectation->fresh();
    }
}
