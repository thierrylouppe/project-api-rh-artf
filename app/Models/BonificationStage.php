<?php

namespace App\Models;

use App\Enums\StatutBonification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bonification de +2 échelons après stage ≥ 9 mois (CCN ARTF art. 71).
 * Parcours séparé du cycle d'évaluation 24 mois.
 */
class BonificationStage extends Model
{
    protected $table = 'bonifications_stage';

    protected $fillable = [
        'agent_id',
        'date_debut_stage',
        'date_fin_stage',
        'duree_mois',
        'type_document',
        'reference_document',
        'nb_echelons',
        'statut',
        'commentaire',
        'traite_par',
        'traite_le',
        'applique_le',
        'applique_par',
        'created_by',
    ];

    protected $casts = [
        'date_debut_stage' => 'date',
        'date_fin_stage'   => 'date',
        'statut'           => StatutBonification::class,
        'traite_le'        => 'datetime',
        'applique_le'      => 'datetime',
        'nb_echelons'      => 'integer',
        'duree_mois'       => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function appliquePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applique_par');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Vérifie si la bonification est déjà appliquée en paie. */
    public function estAppliquee(): bool
    {
        return $this->applique_le !== null;
    }
}
