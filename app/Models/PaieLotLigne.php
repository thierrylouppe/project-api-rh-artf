<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaieLotLigne extends Model
{
    protected $table = 'paie_lot_lignes';

    protected $fillable = [
        'lot_id',
        'agent_id',
        'salaire_agent_id',
        'hors_grille',
        'montant_base',
        'total_gains',
        'total_retenues',
        'montant_net',
        'nb_anomalies',
        'snapshot_agent',
    ];

    protected $casts = [
        'hors_grille' => 'boolean',
        'montant_base' => 'decimal:2',
        'total_gains' => 'decimal:2',
        'total_retenues' => 'decimal:2',
        'montant_net' => 'decimal:2',
        'nb_anomalies' => 'integer',
        'snapshot_agent' => 'array',
    ];

    public function lot(): BelongsTo
    {
        return $this->belongsTo(PaieLot::class, 'lot_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function salaireAgent(): BelongsTo
    {
        return $this->belongsTo(SalaireAgent::class, 'salaire_agent_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PaieLotLigneDetail::class, 'ligne_id');
    }
}
