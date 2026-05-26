<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Localite extends Model
{
    use HasFilterScope;

    protected $fillable = ['nom', 'sigle', 'description'];

    protected array $filterable = ['nom'];

    public function administrations(): HasMany
    {
        return $this->hasMany(Administration::class);
    }
}
