<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemiseMateriel extends Model
{
    use HasFilterScope;

    protected $table = 'remises_materiel';

    protected $fillable = [
        'agent_id',
        'affectation_id',
        'materiel',
        'date_remise',
        'remis_par',
        'pv_path',
    ];

    protected $casts = [
        'materiel'    => 'array',
        'date_remise' => 'date',
    ];

    protected array $filterable = ['agent_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class);
    }

    public function remiseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remis_par');
    }
}
