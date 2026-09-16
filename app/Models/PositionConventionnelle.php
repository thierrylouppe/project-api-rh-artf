<?php

namespace App\Models;

use App\Enums\StatutPositionConventionnelle;
use App\Enums\TypePositionConventionnelle;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionConventionnelle extends Model
{
    use HasFilterScope;

    protected $table = 'positions_conventionnelles';

    protected $fillable = [
        'agent_id',
        'type',
        'statut',
        'date_debut',
        'date_fin',
        'organisme_accueil',
        'consentement_agent',
        'detachement_office',
        'nb_renouvellements',
        'decision_dg_user_id',
        'commentaire',
        'piece_path',
        'created_by',
    ];

    protected $casts = [
        'type'                 => TypePositionConventionnelle::class,
        'statut'               => StatutPositionConventionnelle::class,
        'date_debut'           => 'date',
        'date_fin'             => 'date',
        'consentement_agent'   => 'boolean',
        'detachement_office'   => 'boolean',
        'nb_renouvellements'   => 'integer',
    ];

    protected array $filterable = ['agent_id', 'type', 'statut'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decision_dg_user_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
