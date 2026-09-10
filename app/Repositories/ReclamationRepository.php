<?php

namespace App\Repositories;

use App\Interfaces\ReclamationInterface;
use App\Models\Reclamation;
use Illuminate\Support\Collection;

class ReclamationRepository extends BaseRepository implements ReclamationInterface
{
    protected function model(): string
    {
        return Reclamation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return Reclamation::query()
            ->with(['agent', 'evaluation'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function trouverParEvaluation(int $evaluationId): ?Reclamation
    {
        return Reclamation::query()
            ->where('evaluation_id', $evaluationId)
            ->with(['agent'])
            ->first();
    }
}
