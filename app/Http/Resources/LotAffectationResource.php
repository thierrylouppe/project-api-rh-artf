<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotAffectationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'date_affectation'           => $this->date_affectation?->format('Y-m-d'),
            'motif'                      => $this->motif,
            'note_service'               => $this->note_service,
            'note_service_nom_original'  => $this->note_service_nom_original,
            'statut'                     => $this->statut?->value,
            'statut_label'               => $this->statut?->label(),
            'total'                      => $this->relationLoaded('affectations')
                ? $this->affectations->count()
                : $this->affectations()->count(),
            'affectations'               => AffectationResource::collection($this->whenLoaded('affectations')),
            'validations'                => ValidationWorkflowResource::collection($this->whenLoaded('validations')),
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,
        ];
    }
}
