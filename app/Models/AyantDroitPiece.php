<?php

namespace App\Models;

use App\Enums\TypePieceAyantDroit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AyantDroitPiece extends Model
{
    protected $table = 'ayant_droit_pieces';

    protected $fillable = [
        'ayant_droit_id',
        'type_piece',
        'fichier_path',
        'nom_original',
        'mime_type',
        'taille',
        'uploaded_by',
    ];

    protected $casts = [
        'type_piece' => TypePieceAyantDroit::class,
    ];

    public function ayantDroit(): BelongsTo
    {
        return $this->belongsTo(AyantDroit::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
