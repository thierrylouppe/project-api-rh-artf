<?php

namespace App\Models;

use App\Enums\StatutDossier;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DossierIntegration extends Model
{
    use HasFilterScope;

    protected $table = 'dossiers_integration';

    protected $fillable = [
        'reference',
        'type_integration_id',
        'demandeur_id',
        'structurable_type',
        'structurable_id',
        'poste_demande',
        'nombre_postes',
        'statut',
        'agent_id',
        'date_demande',
        'motif',
        'notes',
    ];

    protected $casts = [
        'date_demande'   => 'date',
        'nombre_postes'  => 'integer',
        'statut'         => StatutDossier::class,
    ];

    protected array $filterable = ['statut', 'type_integration_id', 'demandeur_id'];

    public function typeIntegration(): BelongsTo
    {
        return $this->belongsTo(TypeIntegration::class);
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function structureDemandeur(): MorphTo
    {
        return $this->morphTo('structurable');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DocumentDossier::class);
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(ValidationWorkflow::class, 'validable')->orderBy('ordre');
    }

    public function actes(): HasMany
    {
        return $this->hasMany(ActeAdministratif::class);
    }

    public function priseDeService(): HasOne
    {
        return $this->hasOne(PriseDeService::class);
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(HistoriqueIntegration::class, 'historiable')->latest();
    }

    public function prochaineValidationEnAttente(): ?ValidationWorkflow
    {
        return $this->validations()->where('statut', 'en_attente')->first();
    }

    public function estValideCompletement(): bool
    {
        return $this->validations()->where('statut', '!=', 'approuve')->doesntExist();
    }
}
