<?php

namespace App\Repositories;

use App\Enums\StatutPositionConventionnelle;
use App\Interfaces\PositionConventionnelleInterface;
use App\Models\PositionConventionnelle;
use Illuminate\Support\Collection;

class PositionConventionnelleRepository extends BaseRepository implements PositionConventionnelleInterface
{
    protected function model(): string
    {
        return PositionConventionnelle::class;
    }

    public function getAll(array $filters = []): Collection
    {
        $query = PositionConventionnelle::query()->with(['agent']);

        if (method_exists(PositionConventionnelle::class, 'scopeFilter')) {
            $query->filter($filters);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function parAgent(int $agentId): Collection
    {
        return PositionConventionnelle::query()
            ->with(['agent'])
            ->where('agent_id', $agentId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function existeEnCoursPourAgent(int $agentId): bool
    {
        return PositionConventionnelle::query()
            ->where('agent_id', $agentId)
            ->whereIn('statut', [
                StatutPositionConventionnelle::SOUMISE->value,
                StatutPositionConventionnelle::ACTIVE->value,
            ])
            ->exists();
    }

    public function getActivesEcheantLe(string $date): Collection
    {
        return PositionConventionnelle::query()
            ->where('statut', StatutPositionConventionnelle::ACTIVE->value)
            ->whereDate('date_fin', $date)
            ->with(['agent'])
            ->get();
    }
}
