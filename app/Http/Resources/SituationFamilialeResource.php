<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SituationFamilialeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'agent_id'            => $this->agent_id,
            'statut_matrimonial'  => $this->statut_matrimonial,
            'nb_enfants'          => $this->nb_enfants,
            'updated_at'          => $this->updated_at,
        ];
    }
}
