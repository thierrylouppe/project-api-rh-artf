<?php

namespace App\Models;

use App\Enums\TypePiecePrestation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrestationPiece extends Model
{
    protected $table = 'prestation_pieces';

    protected $fillable = [
        'prestation_id',
        'type_piece',
        'fichier_path',
        'nom_original',
        'mime_type',
        'taille',
        'uploaded_by',
    ];

    protected $casts = [
        'type_piece' => TypePiecePrestation::class,
    ];

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
