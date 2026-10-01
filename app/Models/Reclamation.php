<?php

namespace App\Models;

use App\Enums\StatutReclamation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclamation extends Model
{
    protected $table = 'reclamations';

    protected $fillable = [
        'evaluation_id',
        'agent_id',
        'motif',
        'statut',
        'commentaire_rh',
        'traite_par',
        'traite_le',
    ];

    protected $casts = [
        'statut'    => StatutReclamation::class,
        'traite_le' => 'datetime',
    ];

    // ----------------------------------------------------------------
    // Relations
    // ----------------------------------------------------------------

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'traite_par');
    }
}
