<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscriptionFormationResource extends JsonResource
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
            'formation_id' => $this->when(! $this->relationLoaded('formation'), $this->formation_id),
            'formation' => new CatalogueFormationResource($this->whenLoaded('formation')),
            'plan_id' => $this->plan_id,
            'date_inscription' => $this->date_inscription?->format('Y-m-d'),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'admission_sur_titre' => (bool) $this->admission_sur_titre,
            'rapport_remis' => (bool) $this->rapport_remis,
            'debit_jusqu_au' => $this->debit_jusqu_au?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
