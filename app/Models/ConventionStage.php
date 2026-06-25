<?php

namespace App\Models;

use App\Enums\StatutConventionStage;
use App\Enums\TypeStage;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConventionStage extends Model
{
    use HasFilterScope;

    protected $table = 'conventions_stage';

    protected $fillable = [
        'agent_id',
        'contrat_id',
        'dossier_integration_id',
        'tuteur_interne_id',
        'type_stage',
        'etablissement',
        'date_debut',
        'date_fin',
        'statut_stage',
        'note_finale',
        'appreciation',
    ];

    protected $casts = [
        'date_debut'   => 'date',
        'date_fin'     => 'date',
        'note_finale'  => 'decimal:2',
        'statut_stage' => StatutConventionStage::class,
        'type_stage'   => TypeStage::class,
    ];

    protected array $filterable = ['statut_stage', 'type_stage', 'agent_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function contrat(): BelongsTo
    {
        return $this->belongsTo(Contrat::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierIntegration::class, 'dossier_integration_id');
    }

    public function tuteurInterne(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'tuteur_interne_id');
    }

    public function joursAvantFin(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->date_fin, false);
    }
}
