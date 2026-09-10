<?php

namespace App\Models;

use App\Enums\StatutBonification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avancement exceptionnel (CCN ARTF art. 72).
 * Commission d'avancement, sur proposition DG, ≤ 2 échelons.
 */
class AvancementExceptionnel extends Model
{
    protected $table = 'avancements_exceptionnels';

    protected $fillable = [
        'agent_id',
        'commission_avancement_id',
        'nb_echelons',
        'motif',
        'propose_par',
        'date_proposition',
        'statut',
        'commentaire',
        'traite_par',
        'traite_le',
        'applique_le',
        'applique_par',
    ];

    protected $casts = [
        'date_proposition' => 'date',
        'statut'           => StatutBonification::class,
        'traite_le'        => 'datetime',
        'applique_le'      => 'datetime',
        'nb_echelons'      => 'integer',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function commissionAvancement(): BelongsTo
    {
        return $this->belongsTo(CommissionAvancement::class, 'commission_avancement_id');
    }

    public function proposePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'propose_par');
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par');
    }

    public function appliquePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applique_par');
    }

    public function estApplique(): bool
    {
        return $this->applique_le !== null;
    }
}
