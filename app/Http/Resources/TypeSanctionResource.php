<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeSanctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'code' => $this->code?->value,
            'gravite' => $this->gravite?->value,
            'gravite_label' => $this->gravite?->label(),
            'exige_nb_jours' => (bool) $this->exige_nb_jours,
            'nb_jours_min' => $this->nb_jours_min,
            'nb_jours_max' => $this->nb_jours_max,
            'description' => $this->description,
            'actif' => (bool) $this->actif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
