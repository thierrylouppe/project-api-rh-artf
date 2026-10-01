<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CertificationFormation extends Model
{
    protected $table = 'certifications_formation';

    protected $fillable = [
        'agent_id',
        'formation_id',
        'inscription_id',
        'diplome_id',
        'date_obtention',
        'reference',
        'fichier_path',
        'nom_original',
        'mime_type',
        'taille',
        'uploaded_by',
    ];

    protected $casts = [
        'date_obtention' => 'date',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function formation(): BelongsTo
    {
        return $this->belongsTo(CatalogueFormation::class, 'formation_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(InscriptionFormation::class, 'inscription_id');
    }

    public function diplome(): BelongsTo
    {
        return $this->belongsTo(Diplome::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
