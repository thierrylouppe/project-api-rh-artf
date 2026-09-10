<?php

namespace App\Repositories;

use App\Interfaces\BonificationStageInterface;
use App\Models\BonificationStage;
use Illuminate\Support\Collection;

class BonificationStageRepository extends BaseRepository implements BonificationStageInterface
{
    protected function model(): string { return BonificationStage::class; }

    public function getAll(array $filters = []): Collection
    {
        return BonificationStage::query()->with(['agent'])->orderByDesc('created_at')->get();
    }

    public function parAgent(int $agentId): Collection
    {
        return BonificationStage::query()->where('agent_id', $agentId)->orderByDesc('created_at')->get();
    }

    public function enAttente(): Collection
    {
        return BonificationStage::query()->where('statut', 'en_attente')->with(['agent'])->get();
    }
}
