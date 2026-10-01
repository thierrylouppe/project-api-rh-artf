<?php

namespace App\Models;

use App\Enums\MotifReconversion;
use App\Enums\StatutReclassement;
use App\Enums\TypeReclassement;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reclassement extends Model
{
    use HasFilterScope;

    protected $table = 'reclassements';

    protected $fillable = [
        'agent_id',
        'type',
        'statut',
        'classe_origine_id',
        'classe_cible_id',
        'fonction_cible_id',
        'diplome_id',
        'motif',
        'motif_reconversion',
        'piece_path',
        'age_ans',
        'anciennete_ans',
        'annees_dans_classe',
        'echelon_origine',
        'echelon_cible',
        'created_by',
        'valide_par',
        'valide_at',
        'applique_par',
        'applique_at',
    ];

    protected $casts = [
        'type'               => TypeReclassement::class,
        'statut'             => StatutReclassement::class,
        'motif_reconversion' => MotifReconversion::class,
        'valide_at'          => 'datetime',
        'applique_at'        => 'datetime',
        'age_ans'            => 'integer',
        'anciennete_ans'     => 'integer',
        'annees_dans_classe' => 'integer',
        'echelon_origine'    => 'integer',
        'echelon_cible'      => 'integer',
    ];

    protected array $filterable = ['agent_id', 'type', 'statut'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function classeOrigine(): BelongsTo
    {
        return $this->belongsTo(Classegrillesalariale::class, 'classe_origine_id');
    }

    public function classeCible(): BelongsTo
    {
        return $this->belongsTo(Classegrillesalariale::class, 'classe_cible_id');
    }

    public function fonctionCible(): BelongsTo
    {
        return $this->belongsTo(Fonction::class, 'fonction_cible_id');
    }

    public function diplome(): BelongsTo
    {
        return $this->belongsTo(Diplome::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function appliquePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applique_par');
    }

    public function estApplique(): bool
    {
        return $this->statut === StatutReclassement::APPLIQUE || $this->applique_at !== null;
    }
}
