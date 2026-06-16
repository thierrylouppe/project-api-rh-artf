<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classegrillesalariale extends Model
{
    use HasFilterScope;

    protected $fillable = ['categorie_id', 'grade_id', 'coefficient'];

    protected array $filterable = ['coefficient'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function salaires(): HasMany
    {
        return $this->hasMany(Salaire::class);
    }
}
