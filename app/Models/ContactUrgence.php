<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactUrgence extends Model
{
    protected $table = 'contacts_urgence';

    protected $fillable = [
        'agent_id',
        'nom',
        'prenom',
        'telephone',
        'relation',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
