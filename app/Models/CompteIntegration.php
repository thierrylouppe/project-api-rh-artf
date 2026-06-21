<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompteIntegration extends Model
{
    protected $table = 'comptes_integration';

    protected $fillable = [
        'agent_id',
        'user_id',
        'login',
        'email_professionnel',
        'badge_numero',
        'mot_de_passe_provisoire_envoye',
        'date_creation',
    ];

    protected $casts = [
        'mot_de_passe_provisoire_envoye' => 'boolean',
        'date_creation'                  => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
