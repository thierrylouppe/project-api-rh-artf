<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemiseMaterielResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'agent_id'       => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'          => new AgentResource($this->whenLoaded('agent')),
            'affectation_id' => $this->affectation_id,
            'materiel'       => $this->materiel,
            'date_remise'    => $this->date_remise?->format('Y-m-d'),
            'remis_par'      => $this->remis_par,
            'remiseur'       => new UserResource($this->whenLoaded('remiseur')),
            'pv_path'        => $this->pv_path,
            'created_at'     => $this->created_at,
        ];
    }
}
