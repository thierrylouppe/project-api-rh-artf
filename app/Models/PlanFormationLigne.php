<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanFormationLigne extends Model
{
    protected $table = 'plan_formation_lignes';

    protected $fillable = [
        'plan_id',
        'formation_id',
        'places_prevues',
        'notes',
    ];

    protected $casts = [
        'places_prevues' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanFormation::class, 'plan_id');
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(CatalogueFormation::class, 'formation_id');
    }
}
