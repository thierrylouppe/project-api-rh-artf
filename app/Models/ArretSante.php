<?php

namespace App\Models;

use App\Enums\NatureArretSante;
use App\Enums\StatutDossierSante;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArretSante extends Model
{
    use HasFilterScope;

    protected $table = 'arrets_sante';

    protected $fillable = [
        'agent_id',
        'nature',
        'statut',
        'date_fait',
        'date_notification',
        'date_debut',
        'date_fin',
        'structure_sanitaire_id',
        'demande_conge_id',
        'alerte_72h',
        'nb_mois',
        'nb_mois_majoration',
        'montant_mensuel',
        'montant_mensuel_demi',
        'calcul_snapshot',
        'notes_instruction',
        'commentaire_decision',
        'date_decision',
        'paie_element_affectation_id',
        'created_by',
        'instruite_by',
        'decideur_id',
    ];

    protected $casts = [
        'nature' => NatureArretSante::class,
        'statut' => StatutDossierSante::class,
        'date_fait' => 'date',
        'date_notification' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_decision' => 'date',
        'alerte_72h' => 'boolean',
        'nb_mois' => 'integer',
        'nb_mois_majoration' => 'integer',
        'montant_mensuel' => 'integer',
        'montant_mensuel_demi' => 'integer',
        'calcul_snapshot' => 'array',
    ];

    protected array $filterable = ['agent_id', 'nature', 'statut'];

    protected $attributes = [
        'statut' => 'brouillon',
        'alerte_72h' => false,
        'nb_mois' => 0,
        'nb_mois_majoration' => 0,
        'montant_mensuel' => 0,
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(StructureSanitaire::class, 'structure_sanitaire_id');
    }

    public function demandeConge(): BelongsTo
    {
        return $this->belongsTo(DemandeConge::class, 'demande_conge_id');
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
        return $this->hasMany(ArretSantePiece::class);
    }
}
