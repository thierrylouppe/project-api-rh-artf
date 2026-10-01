<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionAvancementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'session_id'     => $this->session_id,
            'session'        => $this->whenLoaded('session', fn () => [
                'id'           => $this->session->id,
                'debut_session' => $this->session->debut_session?->toDateString(),
                'statut'       => $this->session->statut->value,
            ]),
            'statut'         => $this->statut->value,
            'statut_label'   => $this->statut->label(),
            'date_ouverture' => $this->date_ouverture?->toDateString(),
            'date_cloture'   => $this->date_cloture?->toDateString(),
            'observations'   => $this->observations,
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
