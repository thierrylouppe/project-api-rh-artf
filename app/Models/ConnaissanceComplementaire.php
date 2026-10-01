<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Connaissance complémentaire / besoin de formation identifié pendant l'évaluation.
 */
class ConnaissanceComplementaire extends Model
{
    protected $table = 'connaissances_complementaires';

    protected $fillable = [
        'evaluation_id',
        'type',
        'domaine',
        'description',
        'urgent',
        'created_by',
    ];

    protected $casts = [
        'urgent' => 'boolean',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
