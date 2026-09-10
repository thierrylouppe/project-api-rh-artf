<?php

namespace App\Repositories;

use App\Interfaces\ConnaissanceComplementaireInterface;
use App\Models\ConnaissanceComplementaire;
use Illuminate\Support\Collection;

class ConnaissanceComplementaireRepository extends BaseRepository implements ConnaissanceComplementaireInterface
{
    protected function model(): string { return ConnaissanceComplementaire::class; }

    public function getAll(array $filters = []): Collection
    {
        return ConnaissanceComplementaire::query()->orderByDesc('created_at')->get();
    }

    public function parEvaluation(int $evaluationId): Collection
    {
        return ConnaissanceComplementaire::query()
            ->where('evaluation_id', $evaluationId)
            ->orderByDesc('urgent')
            ->orderBy('created_at')
            ->get();
    }
}
