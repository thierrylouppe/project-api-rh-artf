<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaieAffectationResource extends JsonResource
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
            'paie_element_id' => $this->paie_element_id,
            'element' => new PaieElementResource($this->whenLoaded('element')),
            'montant' => $this->montant !== null ? (float) $this->montant : null,
            'taux' => $this->taux !== null ? (float) $this->taux : null,
            'quantite' => $this->quantite !== null ? (float) $this->quantite : null,
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'motif' => $this->motif,
            'prolongation_dg' => (bool) $this->prolongation_dg,
            'meta' => $this->meta,
            'active' => $this->estActive(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
