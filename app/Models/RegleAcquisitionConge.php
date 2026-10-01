<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegleAcquisitionConge extends Model
{
    use HasFilterScope;

    protected $table = 'regle_acquisition_conges';

    protected $fillable = ['type_conge_id', 'jours_par_mois', 'jours_max'];

    protected $casts = [
        'jours_par_mois' => 'decimal:2',
        'jours_max'      => 'integer',
    ];

    protected array $filterable = ['type_conge_id'];

    public function typeConge(): BelongsTo
    {
        return $this->belongsTo(TypeConge::class);
    }
}
