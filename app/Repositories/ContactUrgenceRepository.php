<?php

namespace App\Repositories;

use App\Interfaces\ContactUrgenceInterface;
use App\Models\ContactUrgence;
use Illuminate\Support\Collection;

class ContactUrgenceRepository extends BaseRepository implements ContactUrgenceInterface
{
    protected function model(): string
    {
        return ContactUrgence::class;
    }

    public function getByAgent(int $agentId): Collection
    {
        return ContactUrgence::query()->where('agent_id', $agentId)->orderBy('nom')->get();
    }
}
