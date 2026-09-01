<?php

namespace App\Models;

use App\Enums\StatutDemandeConge;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeConge extends Model
{
    use HasFilterScope;

    protected $table = 'demande_conges';

    protected $fillable = [
        'agent_id',
        'type_conge_id',
        'date_debut',
        'date_fin',
        'nb_jours',
        'motif',
        'justificatif_path',
        'justificatif_nom_original',
        'statut',
        'created_by',
        'valideur_n1_id',
        'valideur_rh_id',
        'valideur_dg_id',
        'commentaire_n1',
        'commentaire_rh',
        'commentaire_dg',
        'date_validation_n1',
        'date_validation_rh',
        'date_validation_dg',
    ];

    protected $casts = [
        'date_debut'         => 'date',
        'date_fin'           => 'date',
        'nb_jours'           => 'integer',
        'statut'             => StatutDemandeConge::class,
        'date_validation_n1' => 'datetime',
        'date_validation_rh' => 'datetime',
        'date_validation_dg' => 'datetime',
    ];

    protected array $filterable = ['agent_id', 'type_conge_id', 'statut'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function typeConge(): BelongsTo
    {
        return $this->belongsTo(TypeConge::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function valideurN1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valideur_n1_id');
    }

    public function valideurRh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valideur_rh_id');
    }

    public function valideurDg(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valideur_dg_id');
    }
}
