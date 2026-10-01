<?php

namespace App\Models;

use App\Enums\StatutSalaireAgent;
use App\Enums\TypeChangementSalaireAgent;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaireAgent extends Model
{
    use HasFilterScope;

    protected $table = 'salaires_agents';

    protected $fillable = [
        'agent_id',
        'salaire_id',
        'classegrillesalariale_id',
        'echelon',
        'montant_base',
        'montant_net',
        'date_debut',
        'date_fin',
        'statut',
        'type_changement',
        'motif',
    ];

    protected $casts = [
        'montant_base'     => 'float',
        'montant_net'      => 'float',
        'date_debut'       => 'date',
        'date_fin'         => 'date',
        'statut'           => StatutSalaireAgent::class,
        'type_changement'  => TypeChangementSalaireAgent::class,
    ];

    protected array $filterable = ['agent_id', 'statut', 'echelon', 'classegrillesalariale_id', 'type_changement'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function salaire(): BelongsTo
    {
        return $this->belongsTo(Salaire::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classegrillesalariale::class, 'classegrillesalariale_id');
    }
}
