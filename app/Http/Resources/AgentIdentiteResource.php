<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentIdentiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'matricule'   => $this->matricule,
            'nom'         => $this->nom,
            'prenom'      => $this->prenom,
            'nom_complet' => $this->nom_complet,
        ];
    }
}
