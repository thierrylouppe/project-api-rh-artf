<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeCongeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'nom'         => $this->nom,
            'description' => $this->description,
            'jours_max'            => $this->jours_max,
            'necessite_n1'         => (bool) $this->necessite_n1,
            'necessite_rh'         => (bool) $this->necessite_rh,
            'necessite_dg'         => (bool) $this->necessite_dg,
            'debite_solde'         => (bool) $this->debite_solde,
            'justificatif_requis'  => (bool) $this->justificatif_requis,
            'created_at'           => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
