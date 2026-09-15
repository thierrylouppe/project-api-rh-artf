<?php

namespace App\Models;

use App\Enums\CodeTypeSanction;
use App\Enums\GraviteSanction;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeSanction extends Model
{
    use HasFilterScope;

    protected $table = 'type_sanctions';

    protected $fillable = [
        'nom',
        'code',
        'gravite',
        'exige_nb_jours',
        'nb_jours_min',
        'nb_jours_max',
        'description',
        'actif',
    ];

    protected $casts = [
        'code' => CodeTypeSanction::class,
        'gravite' => GraviteSanction::class,
        'exige_nb_jours' => 'boolean',
        'actif' => 'boolean',
    ];

    protected array $filterable = ['nom', 'gravite', 'actif', 'code'];

    protected $attributes = [
        'actif' => true,
        'exige_nb_jours' => false,
    ];

    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    public function estCcn(): bool
    {
        return $this->code instanceof CodeTypeSanction;
    }
}
