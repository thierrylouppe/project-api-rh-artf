<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'agent_id'               => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                  => new AgentResource($this->whenLoaded('agent')),
            'type_contrat_id'        => $this->when(! $this->relationLoaded('typeContrat'), $this->type_contrat_id),
            'type_contrat'           => new TypeContratResource($this->whenLoaded('typeContrat')),
            'fonction_id'            => $this->when(! $this->relationLoaded('fonction'), $this->fonction_id),
            'fonction'               => new FonctionResource($this->whenLoaded('fonction')),
            'date_debut'             => $this->date_debut?->format('Y-m-d'),
            'date_fin'               => $this->date_fin?->format('Y-m-d'),
            'remuneration'           => $this->remuneration,
            'statut'                 => $this->statut,
            'dossier_integration_id' => $this->dossier_integration_id,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
