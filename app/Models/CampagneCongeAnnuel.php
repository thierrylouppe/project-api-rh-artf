<?php

namespace App\Models;

use App\Enums\StatutCampagneCongeAnnuel;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampagneCongeAnnuel extends Model
{
    use HasFilterScope;

    protected $table = 'campagne_conge_annuels';

    protected $fillable = [
        'annee',
        'date_ouverture',
        'date_cloture',
        'date_cloture_effective',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'annee'                  => 'integer',
        'date_ouverture'         => 'date',
        'date_cloture'           => 'date',
        'date_cloture_effective' => 'datetime',
        'statut'                 => StatutCampagneCongeAnnuel::class,
    ];

    protected array $filterable = ['annee', 'statut'];

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeConge::class, 'campagne_conge_annuel_id');
    }
}
