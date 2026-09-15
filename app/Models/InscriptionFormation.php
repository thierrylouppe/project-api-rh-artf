<?php

namespace App\Models;

use App\Enums\StatutInscriptionFormation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InscriptionFormation extends Model
{
    use HasFilterScope;

    protected $table = 'inscriptions_formation';

    protected $fillable = [
        'agent_id',
        'formation_id',
        'plan_id',
        'date_inscription',
        'date_debut',
        'date_fin',
        'statut',
        'admission_sur_titre',
        'rapport_remis',
        'debit_jusqu_au',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'debit_jusqu_au' => 'date',
        'statut' => StatutInscriptionFormation::class,
        'admission_sur_titre' => 'boolean',
        'rapport_remis' => 'boolean',
    ];

    protected array $filterable = ['agent_id', 'formation_id', 'plan_id', 'statut'];

    protected $attributes = [
        'statut' => 'inscrite',
        'admission_sur_titre' => false,
        'rapport_remis' => false,
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(CatalogueFormation::class, 'formation_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanFormation::class, 'plan_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function certification(): HasOne
    {
        return $this->hasOne(CertificationFormation::class, 'inscription_id');
    }
}
