<?php

namespace App\Models;

use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LotNomination extends Model
{
    use HasFilterScope;

    protected $table = 'lot_nominations';

    protected $fillable = [
        'type_acte',
        'date_debut',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'statut'     => StatutNomination::class,
        'type_acte'  => TypeActeNomination::class,
    ];

    protected array $filterable = ['statut', 'type_acte'];

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class, 'lot_nomination_id');
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
