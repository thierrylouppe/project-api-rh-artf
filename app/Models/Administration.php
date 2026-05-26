<?php

namespace App\Models;

use App\Traits\HasAutoSigle;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Administration extends Model
{
    use HasAutoSigle, HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description', 'localite_id'];

    protected array $filterable = ['nom', 'localite_id'];

    public function localite(): BelongsTo
    {
        return $this->belongsTo(Localite::class);
    }

    public function directions(): HasMany
    {
        return $this->hasMany(Direction::class);
    }
}
