<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactUrgenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'agent_id'   => $this->agent_id,
            'nom'        => $this->nom,
            'prenom'     => $this->prenom,
            'telephone'  => $this->telephone,
            'relation'   => $this->relation,
            'created_at' => $this->created_at,
        ];
    }
}
