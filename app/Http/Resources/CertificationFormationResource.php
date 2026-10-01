<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CertificationFormationResource extends JsonResource
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
            'inscription_id' => $this->inscription_id,
            'diplome_id' => $this->when(! $this->relationLoaded('diplome'), $this->diplome_id),
            'diplome' => new DiplomeResource($this->whenLoaded('diplome')),
            'date_obtention' => $this->date_obtention?->format('Y-m-d'),
            'reference' => $this->reference,
            'nom_original' => $this->nom_original,
            'mime_type' => $this->mime_type,
            'taille' => $this->taille,
            'has_fichier' => filled($this->fichier_path),
            'created_at' => $this->created_at,
        ];
    }
}
