<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReclamationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'evaluation_id'  => $this->evaluation_id,
            'agent_id'       => $this->agent_id,
            'agent'          => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'motif'          => $this->motif,
            'statut'         => $this->statut->value,
            'statut_label'   => $this->statut->label(),
            'commentaire_rh' => $this->commentaire_rh,
            'traite_le'      => $this->traite_le?->toDateTimeString(),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
