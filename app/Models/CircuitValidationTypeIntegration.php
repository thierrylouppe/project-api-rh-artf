<?php

namespace App\Models;

use App\Enums\NiveauValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CircuitValidationTypeIntegration extends Model
{
    protected $table = 'circuit_validation_type_integration';

    protected $fillable = [
        'type_integration_id',
        'niveau',
        'ordre',
        'actif',
    ];

    protected $casts = [
        'niveau' => NiveauValidation::class,
        'ordre'  => 'integer',
        'actif'  => 'boolean',
    ];

    public function typeIntegration(): BelongsTo
    {
        return $this->belongsTo(TypeIntegration::class);
    }
}
