<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Salaire extends Model
{
    protected $fillable = ['classegrillesalariale_id', 'echelon', 'indice', 'salaire'];

    protected $casts = [
        'salaire' => 'float',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classegrillesalariale::class, 'classegrillesalariale_id');
    }

    public function salairesAgents(): HasMany
    {
        return $this->hasMany(SalaireAgent::class);
    }
}
