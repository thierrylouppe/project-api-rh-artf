<?php

namespace App\Models;

use App\Enums\StatutEvaluation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFilterScope;

    protected $table = 'evaluations';

    protected $fillable = [
        'session_id',
        'agent_id',
        'superieur_id',
        'date_evaluation',
        'jours_absence_non_justifiee',
        'sanctions',
        'avis_superieur',
        'note_globale',
        'mention',
        'statut',
        'signe_par_evaluateur_at',
        'signe_par_evalue_at',
        'date_validation_rh',
        'validateur_rh_id',
        'commentaire_rh',
        'conforme_rh',
        'created_by',
    ];

    protected $casts = [
        'date_evaluation'             => 'date',
        'jours_absence_non_justifiee' => 'integer',
        'note_globale'                => 'float',
        'statut'                      => StatutEvaluation::class,
        'signe_par_evaluateur_at'     => 'datetime',
        'signe_par_evalue_at'         => 'datetime',
        'date_validation_rh'          => 'datetime',
        'conforme_rh'                 => 'boolean',
    ];

    protected array $filterable = ['session_id', 'agent_id', 'superieur_id', 'statut'];

    // ----------------------------------------------------------------
    // Relations
    // ----------------------------------------------------------------

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionEvaluation::class, 'session_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function superieur(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'superieur_id');
    }

    public function validateurRh(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'validateur_rh_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(NoteEvaluation::class, 'evaluation_id');
    }

    public function reclamation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Reclamation::class, 'evaluation_id');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    public function estModifiable(): bool
    {
        return ! $this->statut->estTerminee();
    }

    public function estCompletelyNoted(): bool
    {
        return $this->notes()->whereHas('question', fn ($q) => $q->where('actif', true))->count()
            === QuestionEvaluation::where('actif', true)->count();
    }
}
