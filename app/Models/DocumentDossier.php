<?php

namespace App\Models;

use App\Traits\HasFilterScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDossier extends Model
{
    use HasFilterScope;

    protected $table = 'documents_dossier';

    protected $fillable = [
        'dossier_integration_id',
        'type_document_id',
        'nom_original',
        'chemin_fichier',
        'est_obligatoire',
        'est_valide',
        'valide_par',
        'date_validation',
        'commentaire',
    ];

    protected $casts = [
        'est_obligatoire'  => 'boolean',
        'est_valide'       => 'boolean',
        'date_validation'  => 'datetime',
    ];

    protected array $filterable = ['dossier_integration_id', 'type_document_id', 'est_valide'];

    public function dossier(): BelongsTo
    {
        return $this->belongsTo(DossierIntegration::class, 'dossier_integration_id');
    }

    public function typeDocument(): BelongsTo
    {
        return $this->belongsTo(TypeDocument::class);
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
}
