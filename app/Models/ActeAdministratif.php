<?php

namespace App\Models;

use App\Enums\TypeActeAdministratif;
use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActeAdministratif extends Model
{
    use HasFilterScope;

    protected $table = 'actes_administratifs';

    protected $fillable = [
        'dossier_integration_id',
        'type_acte',
        'numero',
        'contenu',
        'fichier_path',
        'signe',
        'signe_par',
        'date_signature',
    ];

    protected $casts = [
        'type_acte'      => TypeActeAdministratif::class,
        'signe'          => 'boolean',
        'date_signature' => 'datetime',
    ];

    protected array $filterable = ['dossier_integration_id', 'type_acte', 'signe'];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierIntegration::class, 'dossier_integration_id');
    }

    public function signataire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signe_par');
    }
}
