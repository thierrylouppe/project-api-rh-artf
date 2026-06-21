<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriseDeServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'agent_id'                    => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                       => new AgentResource($this->whenLoaded('agent')),
            'dossier_integration_id'      => $this->dossier_integration_id,
            'responsable_id'              => $this->when(! $this->relationLoaded('responsable'), $this->responsable_id),
            'responsable'                 => new AgentResource($this->whenLoaded('responsable')),
            'date_prise_service'          => $this->date_prise_service?->format('Y-m-d'),
            'confirmation_presence'       => $this->confirmation_presence,
            'confirmation_installation'   => $this->confirmation_installation,
            'confirmation_equipements'    => $this->confirmation_equipements,
            'pv_path'                     => $this->pv_path,
            'observations'                => $this->observations,
            'created_at'                  => $this->created_at,
        ];
    }
}
