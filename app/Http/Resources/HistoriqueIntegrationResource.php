<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriqueIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'action'           => $this->action,
            'ancienne_valeur'  => $this->ancienne_valeur,
            'nouvelle_valeur'  => $this->nouvelle_valeur,
            'commentaire'      => $this->commentaire,
            'utilisateur'      => new UserResource($this->whenLoaded('utilisateur')),
            'created_at'       => $this->created_at,
        ];
    }
}
