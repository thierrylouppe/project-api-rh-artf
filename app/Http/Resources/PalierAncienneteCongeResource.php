<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PalierAncienneteCongeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'anciennete_min' => $this->anciennete_min,
            'anciennete_max' => $this->anciennete_max,
            'jours_bonus'    => $this->jours_bonus,
        ];
    }
}
