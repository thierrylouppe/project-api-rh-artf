<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NominationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'agent_id'          => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'             => new AgentResource($this->whenLoaded('agent')),
            'poste'             => $this->poste,
            'structurable_type' => $this->structurable_type,
            'structurable_id'   => $this->structurable_id,
            'date_debut'        => $this->date_debut?->format('Y-m-d'),
            'date_fin'          => $this->date_fin?->format('Y-m-d'),
            'type_acte'         => $this->type_acte,
            'statut'            => $this->statut,
            'validations'       => ValidationWorkflowResource::collection($this->whenLoaded('validations')),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
