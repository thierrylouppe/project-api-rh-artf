<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avertissement extends Model
{
    use HasFilterScope;

    protected $fillable = [
        'agent_id',
        'motif',
        'date',
        'emetteur_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    protected array $filterable = ['agent_id', 'emetteur_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function emetteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }
}
