<?php

namespace App\Models;

use App\Enums\StatutCommission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Commission d'avancement (CCN ARTF art. 69).
 * Décide de l'avancement (favorable / défavorable / reporté) pour chaque fiche finalisée.
 * Une par session. Présidée par le DG.
 */
class CommissionAvancement extends Model
{
    protected $table = 'commissions_avancements';

    protected $fillable = [
        'session_id',
        'statut',
        'date_ouverture',
        'date_cloture',
        'created_by',
        'cloture_par',
        'observations',
    ];

    protected $casts = [
        'statut'         => StatutCommission::class,
        'date_ouverture' => 'date',
        'date_cloture'   => 'date',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(SessionEvaluation::class, 'session_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function cloturePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloture_par');
    }
}
