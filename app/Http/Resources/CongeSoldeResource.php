<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CongeSoldeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'agent_id'      => $this->agent_id,
            'type_conge_id' => $this->type_conge_id,
            'type_conge'    => new TypeCongeResource($this->whenLoaded('typeConge')),
            'annee'         => $this->annee,
            'solde_initial' => (float) $this->solde_initial,
            'solde_actuel'  => (float) $this->solde_actuel,
        ];
    }
}
