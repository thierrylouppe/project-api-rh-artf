<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanFormationLigneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan_id' => $this->plan_id,
            'formation_id' => $this->when(! $this->relationLoaded('formation'), $this->formation_id),
            'formation' => new CatalogueFormationResource($this->whenLoaded('formation')),
            'places_prevues' => $this->places_prevues,
            'notes' => $this->notes,
        ];
    }
}
