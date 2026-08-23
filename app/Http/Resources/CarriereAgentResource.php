<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarriereAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'matricule'          => $this->matricule,
            'nom'                => $this->nom,
            'prenom'             => $this->prenom,
            'statut'             => $this->statut,
            'contrat_actif'      => new ContratResource($this->whenLoaded('contratActif')),
            'affectation_active' => new AffectationResource($this->whenLoaded('affectationActive')),
            'nomination_active'  => new NominationResource($this->whenLoaded('nominationActive')),
            'salaire_actuel'     => new SalaireAgentResource($this->whenLoaded('salaireActuel')),
        ];
    }
}
