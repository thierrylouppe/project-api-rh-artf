<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonificationStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'agent_id'           => $this->agent_id,
            'agent'              => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'date_debut_stage'   => $this->date_debut_stage?->toDateString(),
            'date_fin_stage'     => $this->date_fin_stage?->toDateString(),
            'duree_mois'         => $this->duree_mois,
            'type_document'      => $this->type_document,
            'reference_document' => $this->reference_document,
            'nb_echelons'        => $this->nb_echelons,
            'statut'             => $this->statut->value,
            'statut_label'       => $this->statut->label(),
            'commentaire'        => $this->commentaire,
            'traite_le'          => $this->traite_le?->toDateTimeString(),
            'applique_le'        => $this->applique_le?->toDateTimeString(),
            'created_at'         => $this->created_at?->toDateTimeString(),
        ];
    }
}
