<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
