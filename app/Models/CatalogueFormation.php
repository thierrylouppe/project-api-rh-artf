<?php

namespace App\Models;

use App\Enums\ModaliteFormation;
use App\Enums\TypeActionFormation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogueFormation extends Model
{
    use HasFilterScope;

    protected $table = 'catalogue_formations';

    protected $fillable = [
        'titre',
        'description',
        'organisme',
        'lieu',
        'modalite',
        'type_action',
        'duree_jours',
        'cout',
        'anciennete_min_ans',
        'debit_formation_mois',
        'actif',
    ];

    protected $casts = [
        'modalite' => ModaliteFormation::class,
        'type_action' => TypeActionFormation::class,
        'duree_jours' => 'integer',
        'cout' => 'decimal:2',
        'anciennete_min_ans' => 'integer',
        'debit_formation_mois' => 'integer',
        'actif' => 'boolean',
    ];

    protected array $filterable = ['titre', 'modalite', 'type_action', 'actif'];

    protected $attributes = [
        'actif' => true,
        'modalite' => 'interne',
        'anciennete_min_ans' => 3,
        'duree_jours' => 1,
    ];

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionFormation::class, 'formation_id');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(CertificationFormation::class, 'formation_id');
    }

    public function lignesPlan(): HasMany
    {
        return $this->hasMany(PlanFormationLigne::class, 'formation_id');
    }
}
