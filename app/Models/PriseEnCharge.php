<?php

namespace App\Models;

use App\Enums\StatutDossierSante;
use App\Enums\TypePriseEnCharge;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriseEnCharge extends Model
{
    use HasFilterScope;

    protected $table = 'prises_en_charge';

    protected $fillable = [
        'agent_id',
        'type',
        'statut',
        'date_soins',
        'ayant_droit_id',
        'structure_sanitaire_id',
        'montant_facture',
        'montant_calcule',
        'montant_accorde',
        'calcul_snapshot',
        'date_debut',
        'date_fin',
        'lieu',
        'at_mp',
        'notes_instruction',
        'commentaire_decision',
        'date_decision',
        'paie_annee',
        'paie_mois',
        'paie_element_affectation_id',
        'created_by',
        'instruite_by',
        'decideur_id',
    ];

    protected $casts = [
        'type' => TypePriseEnCharge::class,
        'statut' => StatutDossierSante::class,
        'date_soins' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_decision' => 'date',
        'at_mp' => 'boolean',
        'montant_facture' => 'integer',
        'montant_calcule' => 'integer',
        'montant_accorde' => 'integer',
        'calcul_snapshot' => 'array',
        'paie_annee' => 'integer',
        'paie_mois' => 'integer',
    ];

    protected array $filterable = ['agent_id', 'type', 'statut'];

    protected $attributes = [
        'statut' => 'brouillon',
        'at_mp' => false,
        'montant_calcule' => 0,
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function ayantDroit(): BelongsTo
    {
        return $this->belongsTo(AyantDroit::class, 'ayant_droit_id');
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(StructureSanitaire::class, 'structure_sanitaire_id');
    }

    public function affectationPaie(): BelongsTo
    {
        return $this->belongsTo(PaieElementAffectation::class, 'paie_element_affectation_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function instructeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instruite_by');
    }

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decideur_id');
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(PriseEnChargePiece::class);
    }
}
