<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'agent_id'                    => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                       => new AgentResource($this->whenLoaded('agent')),
            'structurable_type'           => $this->structurable_type,
            'structurable_id'             => $this->structurable_id,
            'motif'                       => $this->motif,
            'note_service'                => $this->note_service,
            'note_service_nom_original'   => $this->note_service_nom_original,
            'date_affectation'            => $this->date_affectation?->format('Y-m-d'),
            'date_fin'                    => $this->date_fin?->format('Y-m-d'),
            'statut'                      => $this->statut?->value,
            'statut_label'                => $this->statut?->label(),
            'superieur_hierarchique_id'   => $this->superieur_hierarchique_id,
            'superieur_hierarchique'      => new AgentResource($this->whenLoaded('superieurHierarchique')),
            'validations'                 => ValidationWorkflowResource::collection($this->whenLoaded('validations')),
            'created_at'                  => $this->created_at,
            'updated_at'                  => $this->updated_at,
        ];
    }
}
