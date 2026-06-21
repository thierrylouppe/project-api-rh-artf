<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentDossierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'dossier_integration_id' => $this->dossier_integration_id,
            'type_document_id'     => $this->when(! $this->relationLoaded('typeDocument'), $this->type_document_id),
            'type_document'        => new TypeDocumentResource($this->whenLoaded('typeDocument')),
            'nom_original'         => $this->nom_original,
            'chemin_fichier'       => $this->chemin_fichier,
            'est_obligatoire'      => $this->est_obligatoire,
            'est_valide'           => $this->est_valide,
            'date_validation'      => $this->date_validation,
            'commentaire'          => $this->commentaire,
            'validateur'           => new UserResource($this->whenLoaded('validateur')),
            'created_at'           => $this->created_at,
        ];
    }
}
