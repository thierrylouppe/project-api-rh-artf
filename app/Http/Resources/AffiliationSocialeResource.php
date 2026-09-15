<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliationSocialeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'agent' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent ? [
                    'id' => $this->agent->id,
                    'matricule' => $this->agent->matricule,
                    'nom' => $this->agent->nom,
                    'prenom' => $this->agent->prenom,
                    'nom_complet' => $this->agent->nom_complet,
                    'numero_cnss' => $this->agent->numero_cnss,
                    'statut' => $this->agent->statut,
                ] : null
            ),
            'organisme_id' => $this->when(! $this->relationLoaded('organisme'), $this->organisme_id),
            'organisme' => new OrganismeSocialListResource($this->whenLoaded('organisme')),
            'numero_affiliation' => $this->numero_affiliation,
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
