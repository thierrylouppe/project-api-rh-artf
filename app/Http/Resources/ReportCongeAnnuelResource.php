<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportCongeAnnuelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'agent_id'             => $this->agent_id,
            'agent'                => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent ? new AgentIdentiteResource($this->agent) : null
            ),
            'annee_source'         => $this->annee_source,
            'annee_cible'          => $this->annee_cible,
            'jours'                => (float) $this->jours,
            'motif'                => $this->motif,
            'statut'               => $this->statut?->value,
            'statut_label'         => $this->statut?->label(),
            'commentaire_decision' => $this->commentaire_decision,
            'date_decision'        => $this->date_decision,
            'created_at'           => $this->created_at,
        ];
    }
}
