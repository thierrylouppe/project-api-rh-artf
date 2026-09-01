<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InformationsPersonnelleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'agent_id'    => $this->agent_id,
            'adresse'     => $this->adresse,
            'quartier'    => $this->quartier,
            'ville'       => $this->ville,
            'code_postal' => $this->code_postal,
            'pays'        => $this->pays,
            'updated_at'  => $this->updated_at,
        ];
    }
}
