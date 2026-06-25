<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                           => $this->id,
            'nom'                          => $this->nom,
            'description'                  => $this->description,
            'type_acte_administratif'      => $this->type_acte_administratif,
            'necessite_contrat'            => $this->necessite_contrat,
            'necessite_validation_dg'      => $this->necessite_validation_dg,
            'necessite_compte_utilisateur' => $this->necessite_compte_utilisateur,
            'prefixe_matricule'            => $this->prefixe_matricule,
            'duree_max_mois'               => $this->duree_max_mois,
            'documents_obligatoires'       => TypeDocumentResource::collection(
                $this->whenLoaded('documentsObligatoires')
            ),
            'created_at'                   => $this->created_at,
            'updated_at'                   => $this->updated_at,
        ];
    }
}
