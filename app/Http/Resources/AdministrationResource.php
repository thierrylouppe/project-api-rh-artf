<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdministrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'sigle' => $this->sigle,
            'description' => $this->description,
            'localite_id' => $this->when(! $this->relationLoaded('localite'), $this->localite_id),
            'localite' => new StructureOrganisationnelleListResource($this->whenLoaded('localite')),
            'directions' => StructureOrganisationnelleListResource::collection($this->whenLoaded('directions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
