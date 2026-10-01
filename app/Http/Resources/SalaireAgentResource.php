<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaireAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'agent_id'                 => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                    => new AgentResource($this->whenLoaded('agent')),
            'salaire_id'               => $this->salaire_id,
            'classegrillesalariale_id' => $this->classegrillesalariale_id,
            'echelon'                  => $this->echelon,
            'montant_base'             => $this->montant_base,
            'montant_net'              => $this->montant_net,
            'date_debut'               => $this->date_debut?->format('Y-m-d'),
            'date_fin'                 => $this->date_fin?->format('Y-m-d'),
            'statut'                   => $this->statut?->value ?? $this->statut,
            'type_changement'          => $this->type_changement?->value ?? $this->type_changement,
            'type_changement_label'    => $this->type_changement?->label(),
            'motif'                    => $this->motif,
            'echelon_precedent'        => $this->when(
                array_key_exists('echelon_precedent', $this->resource->getAttributes()),
                fn () => $this->echelon_precedent
            ),
            'montant_precedent'        => $this->when(
                array_key_exists('montant_precedent', $this->resource->getAttributes()),
                fn () => $this->montant_precedent
            ),
            'variation_echelon'        => $this->when(
                array_key_exists('variation_echelon', $this->resource->getAttributes()),
                fn () => $this->variation_echelon
            ),
            'variation_montant'        => $this->when(
                array_key_exists('variation_montant', $this->resource->getAttributes()),
                fn () => $this->variation_montant
            ),
            'salaire'                  => $this->whenLoaded('salaire', fn () => new SalaireResource($this->salaire)),
            'classe'                   => $this->whenLoaded('classe', fn () => [
                'id'          => $this->classe->id,
                'coefficient' => $this->classe->coefficient,
                'categorie'   => $this->classe->relationLoaded('categorie') ? [
                    'id'    => $this->classe->categorie->id,
                    'nom'   => $this->classe->categorie->nom,
                    'sigle' => $this->classe->categorie->sigle,
                ] : null,
                'grade'       => $this->classe->relationLoaded('grade') ? [
                    'id'     => $this->classe->grade->id,
                    'nom'    => $this->classe->grade->nom,
                    'niveau' => $this->classe->grade->niveau,
                ] : null,
            ]),
            'created_at'               => $this->created_at,
            'updated_at'               => $this->updated_at,
        ];
    }
}
