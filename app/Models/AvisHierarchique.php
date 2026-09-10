<?php

namespace App\Models;

use App\Enums\NiveauAvisHierarchique;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvisHierarchique extends Model
{
    protected $table = 'avis_hierarchiques';

    protected $fillable = [
        'evaluation_id',
        'niveau',
        'avis',
        'approuve',
        'observations',
        'signe',
        'date_signature',
        'signe_par',
        'ordre',
    ];

    protected $casts = [
        'niveau'         => NiveauAvisHierarchique::class,
        'approuve'       => 'boolean',
        'signe'          => 'boolean',
        'date_signature' => 'datetime',
        'ordre'          => 'integer',
    ];

    // ----------------------------------------------------------------
    // Relations
    // ----------------------------------------------------------------

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'evaluation_id');
    }

    public function signePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signe_par');
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    public function estModifiable(): bool
    {
        return ! $this->signe;
    }
}
