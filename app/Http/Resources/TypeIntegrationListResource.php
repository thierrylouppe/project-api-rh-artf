<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeIntegrationListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                           => $this->id,
            'nom'                          => $this->nom,
            'necessite_contrat'            => $this->necessite_contrat,
            'necessite_validation_dg'      => $this->necessite_validation_dg,
            'necessite_compte_utilisateur' => $this->necessite_compte_utilisateur,
            'prefixe_matricule'            => $this->prefixe_matricule,
            'type_acte_administratif'      => $this->type_acte_administratif,
        ];
    }
}
