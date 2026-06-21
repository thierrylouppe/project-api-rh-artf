<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diplome extends Model
{
    use HasFilterScope;

    protected $fillable = [
        'nom',
        'sigle',
        'description',
        'classegrillesalariale_id',
    ];

    protected array $filterable = ['nom'];

    public function classeGrille(): BelongsTo
    {
        return $this->belongsTo(Classegrillesalariale::class, 'classegrillesalariale_id');
    }
}
