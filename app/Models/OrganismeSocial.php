<?php

namespace App\Models;

use App\Enums\TypeOrganismeSocial;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganismeSocial extends Model
{
    use HasFilterScope;

    protected $table = 'organismes_sociaux';

    protected $fillable = [
        'nom',
        'code',
        'type',
        'description',
        'telephone',
        'email',
        'adresse',
        'actif',
    ];

    protected $casts = [
        'type' => TypeOrganismeSocial::class,
        'actif' => 'boolean',
    ];

    protected array $filterable = ['nom', 'type', 'actif', 'code'];

    protected $attributes = [
        'actif' => true,
    ];

    public function affiliations(): HasMany
    {
        return $this->hasMany(AffiliationSociale::class, 'organisme_id');
    }

    public function estSysteme(): bool
    {
        return $this->code === 'CNSS';
    }
}
