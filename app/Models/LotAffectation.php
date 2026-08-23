<?php

namespace App\Models;

use App\Enums\StatutAffectation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LotAffectation extends Model
{
    use HasFilterScope;

    protected $table = 'lot_affectations';

    protected $fillable = [
        'date_affectation',
        'motif',
        'note_service',
        'note_service_nom_original',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'date_affectation' => 'date',
        'statut'           => StatutAffectation::class,
    ];

    protected array $filterable = ['statut'];

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'lot_affectation_id');
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
