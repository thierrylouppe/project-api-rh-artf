<?php

namespace App\Models;

use App\Enums\StatutPrestation;
use App\Enums\TypePrestation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prestation extends Model
{
    use HasFilterScope;

    protected $table = 'prestations';

    protected $fillable = [
        'agent_id',
        'type',
        'statut',
        'date_fait',
        'ayant_droit_id',
        'beneficiaire_libelle',
        'transport_corps',
        'montant_demande',
        'montant_calcule',
        'montant_accorde',
        'calcul_snapshot',
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
        'type' => TypePrestation::class,
        'statut' => StatutPrestation::class,
        'date_fait' => 'date',
        'date_decision' => 'date',
        'transport_corps' => 'boolean',
        'montant_demande' => 'integer',
        'montant_calcule' => 'integer',
        'montant_accorde' => 'integer',
        'calcul_snapshot' => 'array',
        'paie_annee' => 'integer',
        'paie_mois' => 'integer',
    ];

    protected array $filterable = ['agent_id', 'type', 'statut'];

    protected $attributes = [
        'statut' => 'brouillon',
        'transport_corps' => false,
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
        return $this->hasMany(PrestationPiece::class);
    }
}
