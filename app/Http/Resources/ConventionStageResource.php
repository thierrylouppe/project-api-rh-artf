<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConventionStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'type_stage'             => $this->type_stage?->value,
            'type_stage_label'       => $this->type_stage?->label(),
            'etablissement'          => $this->etablissement,
            'date_debut'             => $this->date_debut?->format('Y-m-d'),
            'date_fin'               => $this->date_fin?->format('Y-m-d'),
            'statut_stage'           => $this->statut_stage?->value,
            'statut_stage_label'     => $this->statut_stage?->label(),
            'note_finale'            => $this->note_finale,
            'appreciation'           => $this->appreciation,
            'jours_avant_fin'        => $this->statut_stage?->estActif() ? $this->joursAvantFin() : null,
            'agent_id'               => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                  => new AgentResource($this->whenLoaded('agent')),
            'contrat_id'             => $this->when(! $this->relationLoaded('contrat'), $this->contrat_id),
            'tuteur_interne_id'      => $this->when(! $this->relationLoaded('tuteurInterne'), $this->tuteur_interne_id),
            'tuteur_interne'         => new AgentResource($this->whenLoaded('tuteurInterne')),
            'dossier_integration_id' => $this->when(! $this->relationLoaded('dossier'), $this->dossier_integration_id),
            'dossier'                => new DossierIntegrationResource($this->whenLoaded('dossier')),
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }
}
