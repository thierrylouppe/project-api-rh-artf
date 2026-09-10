<?php

namespace App\Repositories;

use App\Interfaces\CommissionPreparatoireInterface;
use App\Models\CommissionPreparatoire;
use Illuminate\Support\Collection;

class CommissionPreparatoireRepository extends BaseRepository implements CommissionPreparatoireInterface
{
    protected function model(): string
    {
        return CommissionPreparatoire::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return CommissionPreparatoire::query()->with(['session', 'createur'])->orderByDesc('created_at')->get();
    }

    public function trouverParSession(int $sessionId): ?CommissionPreparatoire
    {
        return CommissionPreparatoire::query()->where('session_id', $sessionId)->first();
    }
}
