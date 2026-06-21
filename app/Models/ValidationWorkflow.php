<?php

namespace App\Models;

use App\Enums\NiveauValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ValidationWorkflow extends Model
{
    protected $table = 'validations_workflow';

    protected $fillable = [
        'validable_type',
        'validable_id',
        'niveau',
        'ordre',
        'validateur_id',
        'statut',
        'commentaire',
        'date_decision',
    ];

    protected $casts = [
        'ordre'          => 'integer',
        'date_decision'  => 'datetime',
        'niveau'         => NiveauValidation::class,
    ];

    public function validable(): MorphTo
    {
        return $this->morphTo();
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }
}
