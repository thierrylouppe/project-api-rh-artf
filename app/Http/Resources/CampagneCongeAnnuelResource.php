<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampagneCongeAnnuelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'annee'                  => $this->annee,
            'date_ouverture'         => $this->date_ouverture?->format('Y-m-d'),
            'date_cloture'           => $this->date_cloture?->format('Y-m-d'),
            'date_cloture_effective' => $this->date_cloture_effective,
            'statut'                 => $this->statut?->value,
            'statut_label'           => $this->statut?->label(),
            'created_at'             => $this->created_at,
        ];
    }
}
