<?php

namespace App\Models;

use App\Enums\LienJuridiqueAyantDroit;
use App\Enums\QualiteAgeAyantDroit;
use App\Enums\TypeAyantDroit;
use App\Traits\HasFilterScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AyantDroit extends Model
{
    use HasFilterScope;

    protected $table = 'ayants_droit';

    protected $fillable = [
        'agent_id',
        'type',
        'nom',
        'prenom',
        'date_naissance',
        'sexe',
        'lien_juridique',
        'qualite_age',
        'date_debut',
        'date_fin',
        'actif',
    ];

    protected $casts = [
        'type' => TypeAyantDroit::class,
        'lien_juridique' => LienJuridiqueAyantDroit::class,
        'qualite_age' => QualiteAgeAyantDroit::class,
        'date_naissance' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
    ];

    protected array $filterable = ['agent_id', 'type', 'actif', 'lien_juridique'];

    protected $attributes = [
        'actif' => true,
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function pieces(): HasMany
    {
        return $this->hasMany(AyantDroitPiece::class);
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function age(?CarbonInterface $au = null): int
    {
        return (int) $this->date_naissance->diffInYears($au ?? now(), true);
    }

    /** Limite d'âge CCN art. 59 (« moins de N ans »). */
    public function ageLimite(): ?int
    {
        if ($this->type !== TypeAyantDroit::ENFANT) {
            return null;
        }

        return ($this->qualite_age ?? QualiteAgeAyantDroit::STANDARD)->ageLimite();
    }

    public function estACharge(?CarbonInterface $au = null): bool
    {
        if (! $this->actif) {
            return false;
        }

        $au ??= now();

        if ($this->date_debut && $this->date_debut->gt($au)) {
            return false;
        }

        if ($this->date_fin && $this->date_fin->lt($au)) {
            return false;
        }

        if ($this->type === TypeAyantDroit::CONJOINT) {
            return true;
        }

        return $this->age($au) < (int) $this->ageLimite();
    }

    /** Arbre de Noël CCN art. 58 : enfants de 0 à 16 ans inclus. */
    public function estEligibleArbreNoel(?CarbonInterface $au = null): bool
    {
        if ($this->type !== TypeAyantDroit::ENFANT || ! $this->actif) {
            return false;
        }

        $age = $this->age($au);

        return $age >= 0 && $age <= 16;
    }
}
