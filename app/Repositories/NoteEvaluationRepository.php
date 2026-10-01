<?php

namespace App\Repositories;

use App\Interfaces\NoteEvaluationInterface;
use App\Models\NoteEvaluation;
use Illuminate\Support\Collection;

class NoteEvaluationRepository extends BaseRepository implements NoteEvaluationInterface
{
    protected function model(): string
    {
        return NoteEvaluation::class;
    }

    public function getByEvaluation(int $evaluationId): Collection
    {
        return NoteEvaluation::query()
            ->with('question')
            ->where('evaluation_id', $evaluationId)
            ->get();
    }

    public function upsert(int $evaluationId, int $questionId, float $noteObtenue, ?string $commentaire): NoteEvaluation
    {
        return NoteEvaluation::updateOrCreate(
            ['evaluation_id' => $evaluationId, 'question_id' => $questionId],
            ['note_obtenue' => $noteObtenue, 'commentaire' => $commentaire],
        );
    }
}
