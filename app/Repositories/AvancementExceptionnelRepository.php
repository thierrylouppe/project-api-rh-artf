<?php

namespace App\Repositories;

use App\Interfaces\AvancementExceptionnelInterface;
use App\Models\AvancementExceptionnel;
use Illuminate\Support\Collection;

class AvancementExceptionnelRepository extends BaseRepository implements AvancementExceptionnelInterface
{
    protected function model(): string { return AvancementExceptionnel::class; }

    public function getAll(array $filters = []): Collection
    {
        return AvancementExceptionnel::query()->with(['agent', 'proposePar'])->orderByDesc('created_at')->get();
    }

    public function parAgent(int $agentId): Collection
    {
        return AvancementExceptionnel::query()->where('agent_id', $agentId)->orderByDesc('created_at')->get();
    }

    public function enAttente(): Collection
    {
        return AvancementExceptionnel::query()->where('statut', 'en_attente')->with(['agent', 'proposePar'])->get();
    }
}
