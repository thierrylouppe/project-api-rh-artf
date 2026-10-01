<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegleAcquisitionCongeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type_conge_id'  => $this->type_conge_id,
            'type_conge'     => new TypeCongeResource($this->whenLoaded('typeConge')),
            'jours_par_mois' => (float) $this->jours_par_mois,
            'jours_max'      => $this->jours_max,
            'created_at'     => $this->created_at,
        ];
    }
}
