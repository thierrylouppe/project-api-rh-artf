<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'matricule'            => $this->matricule,
            'nom'                  => $this->nom,
            'prenom'               => $this->prenom,
            'nom_complet'          => $this->nom_complet,
            'date_naissance'       => $this->date_naissance?->format('Y-m-d'),
            'lieu_naissance'       => $this->lieu_naissance,
            'nationalite'          => $this->nationalite,
            'genre'                => $this->genre,
            'telephone'            => $this->telephone,
            'email_personnel'      => $this->email_personnel,
            'email_professionnel'  => $this->email_professionnel,
            'badge_numero'         => $this->badge_numero,
            'photo_path'           => $this->photo_path,
            'numero_cnss'          => $this->numero_cnss,
            'rib_bancaire'         => $this->rib_bancaire,
            'statut'               => $this->statut,
            'date_prise_service'   => $this->date_prise_service?->format('Y-m-d'),
            'grade_id'             => $this->when(! $this->relationLoaded('grade'), $this->grade_id),
            'grade'                => new GradeResource($this->whenLoaded('grade')),
            'categorie_id'         => $this->when(! $this->relationLoaded('categorie'), $this->categorie_id),
            'categorie'            => new CategorieResource($this->whenLoaded('categorie')),
            'echelon_id'           => $this->when(! $this->relationLoaded('echelon'), $this->echelon_id),
            'echelon'              => new EchelonResource($this->whenLoaded('echelon')),
            'fonction_id'          => $this->when(! $this->relationLoaded('fonction'), $this->fonction_id),
            'fonction'             => new FonctionResource($this->whenLoaded('fonction')),
            'type_integration_id'  => $this->when(! $this->relationLoaded('typeIntegration'), $this->type_integration_id),
            'type_integration'     => new TypeIntegrationResource($this->whenLoaded('typeIntegration')),
            'affectation_active'   => new AffectationResource($this->whenLoaded('affectationActive')),
            'nomination_active'    => new NominationResource($this->whenLoaded('nominationActive')),
            'contrat_actif'        => new ContratResource($this->whenLoaded('contratActif')),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
