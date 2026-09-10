<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'evaluation_id' => $this->evaluation_id,
            'question_id'   => $this->question_id,
            'question'      => $this->whenLoaded('question', fn () => new QuestionEvaluationResource($this->question)),
            'note_obtenue'  => $this->note_obtenue,
            'commentaire'   => $this->commentaire,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
