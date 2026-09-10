<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionEvaluation extends Model
{
    use HasFilterScope;

    protected $table = 'question_evaluations';

    protected $fillable = [
        'libelle',
        'type_critere',
        'bareme_max',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'bareme_max' => 'float',
        'ordre'      => 'integer',
        'actif'      => 'boolean',
    ];

    protected array $filterable = ['type_critere', 'actif'];

    // ----------------------------------------------------------------
    // Relations
    // ----------------------------------------------------------------

    public function notes(): HasMany
    {
        return $this->hasMany(NoteEvaluation::class, 'question_id');
    }

    // ----------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
