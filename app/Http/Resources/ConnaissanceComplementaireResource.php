<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnaissanceComplementaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'evaluation_id' => $this->evaluation_id,
            'type'          => $this->type,
            'domaine'       => $this->domaine,
            'description'   => $this->description,
            'urgent'        => $this->urgent,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
