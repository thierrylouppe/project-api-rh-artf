<?php

namespace App\Models;

use App\Enums\StatutEssai;
use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Nomination extends Model
{
    use HasFilterScope;

    protected $table = 'nominations';

    protected $fillable = [
        'agent_id',
        'poste',
        'structurable_type',
        'structurable_id',
        'date_debut',
        'date_fin',
        'type_acte',
        'soumis_a_essai',
        'essai_statut',
        'duree_essai_mois',
        'date_debut_essai',
        'date_fin_essai',
        'date_confirmation_essai',
        'classegrillesalariale_id',
        'nomination_precedente_id',
        'snapshot_carriere',
        'statut',
        'created_by',
        'lot_nomination_id',
    ];

    protected $casts = [
        'date_debut'               => 'date',
        'date_fin'                 => 'date',
        'statut'                   => StatutNomination::class,
        'type_acte'                => TypeActeNomination::class,
        'soumis_a_essai'           => 'boolean',
        'essai_statut'             => StatutEssai::class,
        'duree_essai_mois'         => 'integer',
        'date_debut_essai'         => 'date',
        'date_fin_essai'           => 'date',
        'date_confirmation_essai'  => 'date',
        'snapshot_carriere'        => 'array',
    ];

    protected array $filterable = ['agent_id', 'statut', 'poste', 'lot_nomination_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function structure(): MorphTo
    {
        return $this->morphTo('structurable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(LotNomination::class, 'lot_nomination_id');
    }

    public function classeCible(): BelongsTo
    {
        return $this->belongsTo(Classegrillesalariale::class, 'classegrillesalariale_id');
    }

    public function nominationPrecedente(): BelongsTo
    {
        return $this->belongsTo(self::class, 'nomination_precedente_id');
    }

    public function validations(): MorphMany
    {
        return $this->morphMany(ValidationWorkflow::class, 'validable')->orderBy('ordre');
    }

    public function historique(): MorphMany
    {
        return $this->morphMany(HistoriqueIntegration::class, 'historiable')->latest();
    }
}
