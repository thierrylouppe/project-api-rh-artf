<?php

namespace App\Repositories;

use App\Interfaces\QuestionEvaluationInterface;
use App\Models\QuestionEvaluation;
use Illuminate\Support\Collection;

class QuestionEvaluationRepository extends BaseRepository implements QuestionEvaluationInterface
{
    protected function model(): string
    {
        return QuestionEvaluation::class;
    }

    public function getAll(array $filters = []): Collection
    {
        return QuestionEvaluation::query()
            ->filter($filters)
            ->orderBy('ordre')
            ->orderBy('id')
            ->get();
    }

    public function getActives(): Collection
    {
        return QuestionEvaluation::query()
            ->where('actif', true)
            ->orderBy('ordre')
            ->orderBy('id')
            ->get();
    }

    public function sommeBaremes(): float
    {
        return (float) QuestionEvaluation::query()
            ->where('actif', true)
            ->sum('bareme_max');
    }
}
