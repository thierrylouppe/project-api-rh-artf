<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentAgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'agent_id'         => $this->agent_id,
            'type_document_id' => $this->when(! $this->relationLoaded('typeDocument'), $this->type_document_id),
            'type_document'    => new TypeDocumentResource($this->whenLoaded('typeDocument')),
            'titre'            => $this->titre,
            'sous_dossier'     => $this->sous_dossier,
            'nom_original'     => $this->nom_original,
            'taille'           => $this->taille,
            'mime_type'        => $this->mime_type,
            'created_at'       => $this->created_at,
        ];
    }
}
