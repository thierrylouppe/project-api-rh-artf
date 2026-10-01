<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;

class PalierAncienneteConge extends Model
{
    use HasFilterScope;

    protected $table = 'palier_anciennete_conges';

    protected $fillable = ['anciennete_min', 'anciennete_max', 'jours_bonus'];

    protected $casts = [
        'anciennete_min' => 'integer',
        'anciennete_max' => 'integer',
        'jours_bonus'    => 'integer',
    ];

    protected array $filterable = ['anciennete_min', 'jours_bonus'];
}
