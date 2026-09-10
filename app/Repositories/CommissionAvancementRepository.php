<?php

namespace App\Repositories;

use App\Interfaces\CommissionAvancementInterface;
use App\Models\CommissionAvancement;
use Illuminate\Support\Collection;

class CommissionAvancementRepository extends BaseRepository implements CommissionAvancementInterface
{
    protected function model(): string
    {
        return CommissionAvancement::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return CommissionAvancement::query()->with(['session', 'createur'])->orderByDesc('created_at')->get();
    }

    public function trouverParSession(int $sessionId): ?CommissionAvancement
    {
        return CommissionAvancement::query()->where('session_id', $sessionId)->first();
    }
}
