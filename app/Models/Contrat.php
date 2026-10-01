<?php

namespace App\Models;

use App\Enums\StatutEssai;
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
        'date_debut_essai',
        'date_fin_essai',
        'duree_essai_mois',
        'essai_renouvele',
        'statut_essai',
        'date_confirmation_essai',
        'lieu_recrutement',
    ];

    protected $casts = [
        'date_debut'              => 'date',
        'date_fin'                => 'date',
        'date_debut_essai'        => 'date',
        'date_fin_essai'          => 'date',
        'date_confirmation_essai' => 'date',
        'remuneration'            => 'decimal:2',
        'essai_renouvele'         => 'boolean',
        'statut_essai'            => StatutEssai::class,
    ];

    protected array $filterable = ['agent_id', 'type_contrat_id', 'statut', 'statut_essai'];

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

    public function essaiEstOuvert(): bool
    {
        return $this->statut_essai?->estOuvert() === true;
    }

    public function peutRenouvelerEssai(): bool
    {
        return $this->statut_essai === StatutEssai::EN_COURS && ! $this->essai_renouvele;
    }

    public function prochaineEtapeEssai(): ?string
    {
        if ($this->essaiEstOuvert()) {
            return 'confirmer-essai';
        }

        return null;
    }
}
