<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AyantDroitResource extends JsonResource
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
                    'statut' => $this->agent->statut,
                ] : null
            ),
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'date_naissance' => $this->date_naissance?->format('Y-m-d'),
            'age' => $this->age(),
            'sexe' => $this->sexe,
            'lien_juridique' => $this->lien_juridique?->value,
            'lien_juridique_label' => $this->lien_juridique?->label(),
            'qualite_age' => $this->qualite_age?->value,
            'qualite_age_label' => $this->qualite_age?->label(),
            'age_limite' => $this->ageLimite(),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'actif' => (bool) $this->actif,
            'a_charge' => $this->estACharge(),
            'eligible_arbre_noel' => $this->estEligibleArbreNoel(),
            'pieces' => AyantDroitPieceResource::collection($this->whenLoaded('pieces')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
