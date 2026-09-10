<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteEvaluation extends Model
{
    protected $table = 'note_evaluations';

    protected $fillable = [
        'evaluation_id',
        'question_id',
        'note_obtenue',
        'commentaire',
    ];

    protected $casts = [
        'note_obtenue' => 'float',
    ];

    // ----------------------------------------------------------------
    // Relations
    // ----------------------------------------------------------------

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionEvaluation::class, 'question_id');
    }
}
