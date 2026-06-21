<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contrat extends Model
{
    use HasFilterScope;

    protected $table = 'contrats';

    protected $fillable = [
        'agent_id',
        'type_contrat_id',
        'dossier_integration_id',
        'fonction_id',
        'date_debut',
        'date_fin',
        'remuneration',
        'statut',
    ];

    protected $casts = [
        'date_debut'   => 'date',
        'date_fin'     => 'date',
        'remuneration' => 'decimal:2',
    ];

    protected array $filterable = ['agent_id', 'type_contrat_id', 'statut'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function typeContrat(): BelongsTo
    {
        return $this->belongsTo(TypeContrat::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierIntegration::class, 'dossier_integration_id');
    }

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class);
    }
}
