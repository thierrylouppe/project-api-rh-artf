<?php

namespace App\Models;

use App\Enums\StatutReportCongeAnnuel;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCongeAnnuel extends Model
{
    use HasFilterScope;

    protected $table = 'report_conge_annuels';

    protected $fillable = [
        'agent_id',
        'annee_source',
        'annee_cible',
        'jours',
        'motif',
        'statut',
        'propose_par',
        'decide_par',
        'commentaire_decision',
        'date_decision',
    ];

    protected $casts = [
        'annee_source'   => 'integer',
        'annee_cible'    => 'integer',
        'jours'          => 'decimal:2',
        'statut'         => StatutReportCongeAnnuel::class,
        'date_decision'  => 'datetime',
    ];

    protected array $filterable = ['agent_id', 'annee_source', 'annee_cible', 'statut'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function proposant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'propose_par');
    }

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decide_par');
    }
}
