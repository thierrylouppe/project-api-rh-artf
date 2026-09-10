<?php

namespace App\Repositories;

use App\Interfaces\SessionEvaluationInterface;
use App\Models\SessionEvaluation;

class SessionEvaluationRepository extends BaseRepository implements SessionEvaluationInterface
{
    protected function model(): string
    {
        return SessionEvaluation::class;
    }

    public function getAll(array $filters = []): \Illuminate\Support\Collection
    {
        return SessionEvaluation::query()
            ->filter($filters)
            ->orderByDesc('debut_session')
            ->get();
    }

    public function trouverOuverte(): ?SessionEvaluation
    {
        return SessionEvaluation::query()
            ->where('statut', 'ouverte')
            ->latest()
            ->first();
    }
}
