<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Agent extends Model
{
    use HasFilterScope;

    protected $table = 'agents';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'nationalite',
        'genre',
        'telephone',
        'email_personnel',
        'email_professionnel',
        'badge_numero',
        'photo_path',
        'numero_cnss',
        'rib_bancaire',
        'grade_id',
        'categorie_id',
        'echelon_id',
        'fonction_id',
        'type_integration_id',
        'date_prise_service',
        'statut',
    ];

    protected $casts = [
        'date_naissance'    => 'date',
        'date_prise_service' => 'date',
    ];

    protected array $filterable = ['nom', 'prenom', 'matricule', 'statut', 'genre'];

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function echelon(): BelongsTo
    {
        return $this->belongsTo(Echelon::class);
    }

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class);
    }

    public function typeIntegration(): BelongsTo
    {
        return $this->belongsTo(TypeIntegration::class);
    }

    public function compte(): HasOne
    {
        return $this->hasOne(CompteIntegration::class);
    }

    public function contrats(): HasMany
    {
        return $this->hasMany(Contrat::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(Nomination::class);
    }

    public function prisesDeService(): HasMany
    {
        return $this->hasMany(PriseDeService::class);
    }

    public function remisesMateriel(): HasMany
    {
        return $this->hasMany(RemiseMateriel::class);
    }

    public function dossierIntegration(): HasOne
    {
        return $this->hasOne(DossierIntegration::class);
    }

    public function conventionStageActive(): HasOne
    {
        return $this->hasOne(ConventionStage::class)->where('statut_stage', 'EN_COURS')->latest();
    }

    public function conventionsStage(): HasMany
    {
        return $this->hasMany(ConventionStage::class);
    }

    public function affectationActive(): HasOne
    {
        return $this->hasOne(Affectation::class)->where('statut', 'active')->latest();
    }

    public function nominationActive(): HasOne
    {
        return $this->hasOne(Nomination::class)->where('statut', 'active')->latest();
    }

    public function contratActif(): HasOne
    {
        return $this->hasOne(Contrat::class)->where('statut', 'actif')->latest();
    }
}
