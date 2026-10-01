<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotNominationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'date_debut'   => $this->date_debut?->format('Y-m-d'),
            'type_acte'    => $this->type_acte?->value ?? $this->type_acte,
            'type_acte_label' => $this->type_acte?->label(),
            'statut'       => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'nominations'  => NominationResource::collection($this->whenLoaded('nominations')),
            'validations'  => ValidationWorkflowResource::collection($this->whenLoaded('validations')),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
