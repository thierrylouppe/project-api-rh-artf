<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AbsenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'agent_id'        => $this->agent_id,
            'agent'           => new AgentResource($this->whenLoaded('agent')),
            'type_absence_id' => $this->type_absence_id,
            'type_absence'    => new TypeAbsenceResource($this->whenLoaded('typeAbsence')),
            'date_debut'      => $this->date_debut?->format('Y-m-d'),
            'date_fin'        => $this->date_fin?->format('Y-m-d'),
            'nb_jours'        => $this->nb_jours,
            'justifiee'       => (bool) $this->justifiee,
            'motif'           => $this->motif,
            'statut'          => $this->statut?->value,
            'statut_label'    => $this->statut?->label(),
            'created_at'      => $this->created_at,
        ];
    }
}
