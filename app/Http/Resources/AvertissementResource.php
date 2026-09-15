<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvertissementResource extends JsonResource
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
            'motif' => $this->motif,
            'date' => $this->date?->format('Y-m-d'),
            'emetteur_id' => $this->emetteur_id,
            'emetteur' => $this->when(
                $this->relationLoaded('emetteur'),
                fn () => $this->emetteur ? [
                    'id' => $this->emetteur->id,
                    'name' => $this->emetteur->name,
                ] : null
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
