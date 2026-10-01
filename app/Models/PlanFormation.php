<?php

namespace App\Models;

use App\Enums\StatutPlanFormation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanFormation extends Model
{
    use HasFilterScope;

    protected $table = 'plans_formation';

    protected $fillable = [
        'annee',
        'titre',
        'description',
        'statut',
        'created_by',
        'valide_par',
        'valide_at',
    ];

    protected $casts = [
        'annee' => 'integer',
        'statut' => StatutPlanFormation::class,
        'valide_at' => 'datetime',
    ];

    protected array $filterable = ['annee', 'statut'];

    protected $attributes = [
        'statut' => 'brouillon',
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(PlanFormationLigne::class, 'plan_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionFormation::class, 'plan_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
}
