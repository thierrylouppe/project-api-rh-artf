<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InformationsProfessionnelleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'agent_id'           => $this->agent_id,
            'diplome_id'         => $this->when(! $this->relationLoaded('diplome'), $this->diplome_id),
            'diplome'            => new DiplomeResource($this->whenLoaded('diplome')),
            'niveau_etude'       => $this->niveau_etude,
            'specialite'         => $this->specialite,
            'annees_experience'  => $this->annees_experience,
            'etablissement'      => $this->etablissement,
            'updated_at'         => $this->updated_at,
        ];
    }
}
