<?php

namespace App\Models;

use App\Enums\TypeVisiteMedicale;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisiteMedicale extends Model
{
    use HasFilterScope;

    protected $table = 'visites_medicales';

    protected $fillable = [
        'agent_id',
        'type',
        'date_visite',
        'structure_sanitaire_id',
        'observations',
        'created_by',
    ];

    protected $casts = [
        'type' => TypeVisiteMedicale::class,
        'date_visite' => 'date',
    ];

    protected array $filterable = ['agent_id', 'type', 'structure_sanitaire_id'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function structure(): BelongsTo
    {
        return $this->belongsTo(StructureSanitaire::class, 'structure_sanitaire_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
