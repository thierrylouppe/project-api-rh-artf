<?php

namespace App\Models;

use App\Enums\StatutSanction;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sanction extends Model
{
    use HasFilterScope;
    use SoftDeletes;

    protected $fillable = [
        'agent_id',
        'type_sanction_id',
        'motif',
        'date_faits',
        'nb_jours',
        'avec_indemnite',
        'date_debut_effet',
        'date_fin_effet',
        'notes_instruction',
        'date_decision',
        'decision',
        'statut',
        'created_by',
        'validateur_id',
        'commentaire_validation',
        'conservee_jusqu_au',
    ];

    protected $casts = [
        'date_faits' => 'date',
        'date_debut_effet' => 'date',
        'date_fin_effet' => 'date',
        'date_decision' => 'date',
        'conservee_jusqu_au' => 'date',
        'avec_indemnite' => 'boolean',
        'statut' => StatutSanction::class,
    ];

    protected array $filterable = ['agent_id', 'type_sanction_id', 'statut', 'created_by'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function typeSanction(): BelongsTo
    {
        return $this->belongsTo(TypeSanction::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(SanctionPiece::class);
    }
}
