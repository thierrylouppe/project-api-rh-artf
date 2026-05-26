<?php

namespace App\Models;

use App\Traits\HasAutoSigle;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasAutoSigle, HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description', 'direction_id'];

    protected array $filterable = ['nom', 'direction_id'];

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function bureaux(): HasMany
    {
        return $this->hasMany(Bureau::class);
    }
}
