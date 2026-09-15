<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SanctionPiece extends Model
{
    protected $fillable = [
        'sanction_id',
        'fichier_path',
        'nom_original',
        'mime_type',
        'taille',
        'uploaded_by',
    ];

    public function sanction(): BelongsTo
    {
        return $this->belongsTo(Sanction::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
