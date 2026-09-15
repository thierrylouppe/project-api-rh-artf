<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatalogueFormationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titre' => $this->titre,
            'description' => $this->description,
            'organisme' => $this->organisme,
            'lieu' => $this->lieu,
            'modalite' => $this->modalite?->value,
            'modalite_label' => $this->modalite?->label(),
            'type_action' => $this->type_action?->value,
            'type_action_label' => $this->type_action?->label(),
            'duree_jours' => $this->duree_jours,
            'duree_max_mois' => $this->type_action?->dureeMaxMois(),
            'cout' => $this->cout,
            'anciennete_min_ans' => $this->anciennete_min_ans,
            'debit_formation_mois' => $this->debit_formation_mois,
            'actif' => (bool) $this->actif,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
