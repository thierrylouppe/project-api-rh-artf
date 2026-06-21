<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Nomination extends Model
{
    use HasFilterScope;

    protected $table = 'nominations';

    protected $fillable = [
        'agent_id',
        'poste',
        'structurable_type',
        'structurable_id',
        'date_debut',
        'date_fin',
        'type_acte',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    protected array $filterable = ['agent_id', 'statut', 'poste'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function structure(): MorphTo
    {
        return $this->morphTo('structurable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(ValidationWorkflow::class, 'validable')->orderBy('ordre');
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(HistoriqueIntegration::class, 'historiable')->latest();
    }
}
