<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class HistoriqueIntegration extends Model
{
    protected $table = 'historiques_integration';

    protected $fillable = [
        'historiable_type',
        'historiable_id',
        'utilisateur_id',
        'action',
        'ancienne_valeur',
        'nouvelle_valeur',
        'commentaire',
    ];

    protected $casts = [
        'ancienne_valeur' => 'array',
        'nouvelle_valeur' => 'array',
    ];

    public function historiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
