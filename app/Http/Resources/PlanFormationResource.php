<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanFormationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'annee' => $this->annee,
            'titre' => $this->titre,
            'description' => $this->description,
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'lignes' => PlanFormationLigneResource::collection($this->whenLoaded('lignes')),
            'createur' => $this->when(
                $this->relationLoaded('createur'),
                fn () => $this->createur ? ['id' => $this->createur->id, 'name' => $this->createur->name] : null
            ),
            'validateur' => $this->when(
                $this->relationLoaded('validateur'),
                fn () => $this->validateur ? ['id' => $this->validateur->id, 'name' => $this->validateur->name] : null
            ),
            'valide_at' => $this->valide_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
