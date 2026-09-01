<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CongeSolde extends Model
{
    use HasFilterScope;

    protected $table = 'conge_soldes';

    protected $fillable = [
        'agent_id',
        'type_conge_id',
        'annee',
        'solde_initial',
        'solde_actuel',
    ];

    protected $casts = [
        'annee'          => 'integer',
        'solde_initial'  => 'decimal:2',
        'solde_actuel'   => 'decimal:2',
    ];

    protected array $filterable = ['agent_id', 'type_conge_id', 'annee'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function typeConge(): BelongsTo
    {
        return $this->belongsTo(TypeConge::class);
    }
}
