<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriseDeService extends Model
{
    use HasFilterScope;

    protected $table = 'prises_de_service';

    protected $fillable = [
        'agent_id',
        'dossier_integration_id',
        'responsable_id',
        'date_prise_service',
        'confirmation_presence',
        'confirmation_installation',
        'confirmation_equipements',
        'pv_path',
        'observations',
    ];

    protected $casts = [
        'date_prise_service'        => 'date',
        'confirmation_presence'     => 'boolean',
        'confirmation_installation' => 'boolean',
        'confirmation_equipements'  => 'boolean',
    ];

    protected array $filterable = ['agent_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierIntegration::class, 'dossier_integration_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'responsable_id');
    }
}
