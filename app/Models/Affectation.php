<?php

namespace App\Models;

use App\Enums\StatutAffectation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Affectation extends Model
{
    use HasFilterScope;

    protected $table = 'affectations';

    protected $fillable = [
        'agent_id',
        'structurable_type',
        'structurable_id',
        'motif',
        'note_service',
        'note_service_nom_original',
        'superieur_hierarchique_id',
        'date_affectation',
        'date_fin',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'date_affectation' => 'date',
        'date_fin'         => 'date',
        'statut'           => StatutAffectation::class,
    ];

    protected array $filterable = ['agent_id', 'statut'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function structure(): MorphTo
    {
        return $this->morphTo('structurable');
    }

    public function superieurHierarchique(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'superieur_hierarchique_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(ValidationWorkflow::class, 'validable')->orderBy('ordre');
    }

    public function remisesMateriel(): HasMany
    {
        return $this->hasMany(RemiseMateriel::class);
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(HistoriqueIntegration::class, 'historiable')->latest();
    }
}
