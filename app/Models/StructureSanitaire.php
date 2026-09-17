<?php

namespace App\Models;

use App\Enums\TypeStructureSanitaire;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StructureSanitaire extends Model
{
    use HasFilterScope;

    protected $table = 'structures_sanitaires';

    protected $fillable = [
        'nom',
        'type',
        'ville',
        'telephone',
        'adresse',
        'actif',
    ];

    protected $casts = [
        'type' => TypeStructureSanitaire::class,
        'actif' => 'boolean',
    ];

    protected array $filterable = ['nom', 'type', 'actif'];

    protected $attributes = [
        'actif' => true,
    ];

    public function visites(): HasMany
    {
        return $this->hasMany(VisiteMedicale::class);
    }

    public function prisesEnCharge(): HasMany
    {
        return $this->hasMany(PriseEnCharge::class);
    }

    public function arrets(): HasMany
    {
        return $this->hasMany(ArretSante::class);
    }
}
