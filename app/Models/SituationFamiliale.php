<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SituationFamiliale extends Model
{
    protected $table = 'situations_familiales';

    protected $fillable = [
        'agent_id',
        'statut_matrimonial',
        'nb_enfants',
    ];

    protected $casts = [
        'nb_enfants' => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
