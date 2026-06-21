<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                              => $this->id,
            'agent_id'                        => $this->agent_id,
            'user_id'                         => $this->user_id,
            'login'                           => $this->login,
            'email_professionnel'             => $this->email_professionnel,
            'badge_numero'                    => $this->badge_numero,
            'mot_de_passe_provisoire_envoye'  => $this->mot_de_passe_provisoire_envoye,
            'date_creation'                   => $this->date_creation,
            'agent'                           => new AgentResource($this->whenLoaded('agent')),
            'created_at'                      => $this->created_at,
        ];
    }
}
