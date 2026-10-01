<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaieElementAffectation extends Model
{
    use HasFilterScope;

    protected $table = 'paie_element_affectations';

    protected $fillable = [
        'paie_element_id',
        'agent_id',
        'montant',
        'taux',
        'quantite',
        'date_debut',
        'date_fin',
        'motif',
        'prolongation_dg',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'taux' => 'decimal:4',
        'quantite' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'prolongation_dg' => 'boolean',
        'meta' => 'array',
    ];

    protected array $filterable = ['agent_id', 'paie_element_id'];

    protected $attributes = [
        'prolongation_dg' => false,
    ];

    public function element(): BelongsTo
    {
        return $this->belongsTo(PaieElement::class, 'paie_element_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estActive(?CarbonInterface $au = null): bool
    {
        $au ??= now()->startOfDay();

        if ($this->date_debut && $this->date_debut->gt($au)) {
            return false;
        }

        if ($this->date_fin && $this->date_fin->lt($au)) {
            return false;
        }

        return true;
    }
}
