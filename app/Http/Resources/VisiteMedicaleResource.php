<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisiteMedicaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'agent' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent ? new AgentIdentiteResource($this->agent) : null
            ),
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'date_visite' => $this->date_visite?->format('Y-m-d'),
            'structure_sanitaire_id' => $this->structure_sanitaire_id,
            'structure' => $this->when(
                $this->relationLoaded('structure'),
                fn () => $this->structure ? [
                    'id' => $this->structure->id,
                    'nom' => $this->structure->nom,
                    'type' => $this->structure->type?->value,
                ] : null
            ),
            'observations' => $this->observations,
            'created_at' => $this->created_at,
        ];
    }
}
