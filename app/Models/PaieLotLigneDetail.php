<?php

namespace App\Models;

use App\Enums\NaturePaieElement;
use App\Enums\SensPaieElement;
use App\Enums\SourceDetailPaie;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaieLotLigneDetail extends Model
{
    protected $table = 'paie_lot_ligne_details';

    protected $fillable = [
        'ligne_id',
        'paie_element_id',
        'code',
        'libelle',
        'nature',
        'sens',
        'montant',
        'source',
        'meta',
    ];

    protected $casts = [
        'nature' => NaturePaieElement::class,
        'sens' => SensPaieElement::class,
        'source' => SourceDetailPaie::class,
        'montant' => 'decimal:2',
        'meta' => 'array',
    ];

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(PaieLotLigne::class, 'ligne_id');
    }

    public function element(): BelongsTo
    {
        return $this->belongsTo(PaieElement::class, 'paie_element_id');
    }
}
