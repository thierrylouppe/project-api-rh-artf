<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InformationsProfessionnelle extends Model
{
    protected $table = 'informations_professionnelles';

    protected $fillable = [
        'agent_id',
        'diplome_id',
        'niveau_etude',
        'specialite',
        'annees_experience',
        'etablissement',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function diplome(): BelongsTo
    {
        return $this->belongsTo(Diplome::class);
    }
}
