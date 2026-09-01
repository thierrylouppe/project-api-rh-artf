<?php

namespace App\Models;

use App\Enums\StatutAbsence;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use HasFilterScope;

    protected $fillable = [
        'agent_id',
        'type_absence_id',
        'date_debut',
        'date_fin',
        'nb_jours',
        'justifiee',
        'motif',
        'statut',
        'created_by',
        'valideur_id',
        'commentaire_validation',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'nb_jours'   => 'integer',
        'justifiee'  => 'boolean',
        'statut'     => StatutAbsence::class,
    ];

    protected array $filterable = ['agent_id', 'type_absence_id', 'statut', 'justifiee'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function typeAbsence(): BelongsTo
    {
        return $this->belongsTo(TypeAbsence::class);
    }

    public function valideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valideur_id');
    }
}
