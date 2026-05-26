<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'sigle' => $this->sigle,
            'description' => $this->description,
            'direction_id' => $this->when(! $this->relationLoaded('direction'), $this->direction_id),
            'direction' => new DirectionResource($this->whenLoaded('direction')),
            'bureaux' => BureauResource::collection($this->whenLoaded('bureaux')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
