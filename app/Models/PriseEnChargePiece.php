<?php

namespace App\Models;

use App\Enums\TypePieceSante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriseEnChargePiece extends Model
{
    protected $table = 'prise_en_charge_pieces';

    protected $fillable = [
        'prise_en_charge_id',
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

    public function priseEnCharge(): BelongsTo
    {
        return $this->belongsTo(PriseEnCharge::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
