<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DossierIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'reference'            => $this->reference,
            'statut'               => $this->statut?->value,
            'statut_label'         => $this->statut?->label(),
            'date_demande'         => $this->date_demande?->format('Y-m-d'),
            'poste_demande'        => $this->poste_demande,
            'nombre_postes'        => $this->nombre_postes,
            'motif'                => $this->motif,
            'notes'                => $this->notes,
            'type_integration_id'  => $this->when(! $this->relationLoaded('typeIntegration'), $this->type_integration_id),
            'type_integration'     => new TypeIntegrationResource($this->whenLoaded('typeIntegration')),
            'demandeur_id'         => $this->when(! $this->relationLoaded('demandeur'), $this->demandeur_id),
            'demandeur'            => new UserResource($this->whenLoaded('demandeur')),
            'agent_id'             => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                => new AgentResource($this->whenLoaded('agent')),
            'documents'            => DocumentDossierResource::collection($this->whenLoaded('documents')),
            'validations'          => ValidationWorkflowResource::collection($this->whenLoaded('validations')),
            'actes'                => ActeAdministratifResource::collection($this->whenLoaded('actes')),
            'historique'           => HistoriqueIntegrationResource::collection($this->whenLoaded('historique')),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
