<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvancementExceptionnelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'agent_id'                 => $this->agent_id,
            'agent'                    => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'commission_avancement_id' => $this->commission_avancement_id,
            'nb_echelons'              => $this->nb_echelons,
            'motif'                    => $this->motif,
            'date_proposition'         => $this->date_proposition?->toDateString(),
            'statut'                   => $this->statut->value,
            'statut_label'             => $this->statut->label(),
            'commentaire'              => $this->commentaire,
            'traite_le'                => $this->traite_le?->toDateTimeString(),
            'applique_le'              => $this->applique_le?->toDateTimeString(),
            'created_at'               => $this->created_at?->toDateTimeString(),
        ];
    }
}
