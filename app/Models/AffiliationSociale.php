<?php

namespace App\Models;

use App\Enums\StatutAffiliation;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliationSociale extends Model
{
    use HasFilterScope;

    protected $table = 'affiliations_sociales';

    protected $fillable = [
        'agent_id',
        'organisme_id',
        'numero_affiliation',
        'date_debut',
        'date_fin',
        'statut',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'statut' => StatutAffiliation::class,
    ];

    protected array $filterable = ['agent_id', 'organisme_id', 'statut'];

    protected $attributes = [
        'statut' => 'active',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function organisme(): BelongsTo
    {
        return $this->belongsTo(OrganismeSocial::class, 'organisme_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estActive(): bool
    {
        return $this->statut === StatutAffiliation::ACTIVE;
    }
}
