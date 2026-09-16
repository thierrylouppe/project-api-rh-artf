<?php

namespace App\Models;

use App\Enums\StatutPaieLot;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaieLot extends Model
{
    use HasFilterScope;

    protected $table = 'paie_lots';

    protected $fillable = [
        'annee',
        'mois',
        'statut',
        'generated_at',
        'generated_by',
        'controle_at',
        'controle_par',
        'valide_at',
        'valide_par',
        'cloture_at',
        'cloture_par',
        'commentaire',
        'anomalies',
        'total_gains',
        'total_retenues',
        'total_net',
        'nb_lignes',
    ];

    protected $casts = [
        'annee' => 'integer',
        'mois' => 'integer',
        'statut' => StatutPaieLot::class,
        'generated_at' => 'datetime',
        'controle_at' => 'datetime',
        'valide_at' => 'datetime',
        'cloture_at' => 'datetime',
        'anomalies' => 'array',
        'total_gains' => 'decimal:2',
        'total_retenues' => 'decimal:2',
        'total_net' => 'decimal:2',
        'nb_lignes' => 'integer',
    ];

    protected array $filterable = ['annee', 'mois', 'statut'];

    protected $attributes = [
        'statut' => 'brouillon',
        'total_gains' => 0,
        'total_retenues' => 0,
        'total_net' => 0,
        'nb_lignes' => 0,
    ];

    public function lignes(): HasMany
    {
        return $this->hasMany(PaieLotLigne::class, 'lot_id');
    }

    public function generateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function controleur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'controle_par');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function clotureur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cloture_par');
    }

    public function nbAnomaliesBloquantes(): int
    {
        return collect($this->anomalies ?? [])
            ->where('severite', 'bloquante')
            ->count();
    }

    public function periodeCode(): string
    {
        return sprintf('%04d-%02d', $this->annee, $this->mois);
    }

    public function periodeLabel(): string
    {
        $mois = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];

        return ($mois[(int) $this->mois] ?? (string) $this->mois).' '.$this->annee;
    }
}
