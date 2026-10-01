<?php

namespace App\Models;

use App\Enums\StatutSessionEvaluation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionEvaluation extends Model
{
    use HasFilterScope;

    protected $table = 'session_evaluations';

    protected $fillable = [
        'debut_session',
        'fin_session',
        'statut',
        'type_annee',
        'semestre',
        'description',
        'created_by',
        'cloturee_par',
        'cloturee_at',
    ];

    protected $casts = [
        'debut_session' => 'date',
        'fin_session'   => 'date',
        'statut'        => StatutSessionEvaluation::class,
        'cloturee_at'   => 'datetime',
    ];

    protected array $filterable = ['statut', 'type_annee'];

    // ----------------------------------------------------------------
    // Relations
    // ----------------------------------------------------------------

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'session_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cloturee_par');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    public function estOuverte(): bool
    {
        return $this->statut === StatutSessionEvaluation::OUVERTE;
    }
}
