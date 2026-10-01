<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlerteDelaiContratResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'dossier_id'         => $this->id,
            'reference'          => $this->reference,
            'agent_id'           => $this->agent_id,
            'agent'              => $this->agent ? [
                'id'          => $this->agent->id,
                'matricule'   => $this->agent->matricule,
                'nom'         => $this->agent->nom,
                'prenom'      => $this->agent->prenom,
                'nom_complet' => $this->agent->nom_complet,
            ] : null,
            'type_integration'   => $this->relationLoaded('typeIntegration')
                ? $this->typeIntegration?->nom
                : null,
            'date_prise_service' => $this->agent?->date_prise_service?->format('Y-m-d'),
            'jours_ouvrables'    => $this->jours_ouvrables,
        ];
    }
}
