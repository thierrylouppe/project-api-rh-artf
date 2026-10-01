<?php

namespace App\Models;

use App\Enums\TypePieceSante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArretSantePiece extends Model
{
    protected $table = 'arret_sante_pieces';

    protected $fillable = [
        'arret_sante_id',
        'type_piece',
        'fichier_path',
        'nom_original',
        'mime_type',
        'taille',
        'uploaded_by',
    ];

    protected $casts = [
        'type_piece' => TypePieceSante::class,
    ];

    public function arret(): BelongsTo
    {
        return $this->belongsTo(ArretSante::class, 'arret_sante_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
