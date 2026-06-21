<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActeAdministratifResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'dossier_integration_id' => $this->dossier_integration_id,
            'type_acte'              => $this->type_acte?->value,
            'type_acte_label'        => $this->type_acte?->label(),
            'numero'                 => $this->numero,
            'contenu'                => $this->contenu,
            'fichier_path'           => $this->fichier_path,
            'signe'                  => $this->signe,
            'date_signature'         => $this->date_signature,
            'signataire'             => new UserResource($this->whenLoaded('signataire')),
            'created_at'             => $this->created_at,
        ];
    }
}
