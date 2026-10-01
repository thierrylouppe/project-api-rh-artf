<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationsPersonnelle extends Model
{
    protected $table = 'informations_personnelles';

    protected $fillable = [
        'agent_id',
        'adresse',
        'quartier',
        'ville',
        'code_postal',
        'pays',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
