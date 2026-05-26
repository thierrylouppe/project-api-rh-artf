<?php

namespace App\Models;

use App\Traits\HasAutoSigle;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Direction extends Model
{
    use HasAutoSigle, HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description', 'administration_id'];

    protected array $filterable = ['nom', 'administration_id'];

    public function administration(): BelongsTo
    {
        return $this->belongsTo(Administration::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
