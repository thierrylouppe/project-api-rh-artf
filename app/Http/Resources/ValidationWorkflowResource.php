<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidationWorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'niveau'         => $this->niveau?->value,
            'niveau_label'   => $this->niveau?->label(),
            'ordre'          => $this->ordre,
            'statut'         => $this->statut,
            'commentaire'    => $this->commentaire,
            'date_decision'  => $this->date_decision,
            'validateur'     => new UserResource($this->whenLoaded('validateur')),
            'created_at'     => $this->created_at,
        ];
    }
}
