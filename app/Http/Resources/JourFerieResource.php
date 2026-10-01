<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourFerieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nom'        => $this->nom,
            'date'       => $this->date?->format('Y-m-d'),
            'recurrent'  => (bool) $this->recurrent,
            'created_at' => $this->created_at,
        ];
    }
}
