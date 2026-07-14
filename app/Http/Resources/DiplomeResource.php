<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiplomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'nom'                      => $this->nom,
            'sigle'                    => $this->sigle ?? null,
            'description'              => $this->description,
            'classegrillesalariale_id' => $this->classegrillesalariale_id,
            'classe_grille'            => $this->whenLoaded('classeGrille', function () {
                if (! $this->classeGrille) {
                    return null;
                }

                return [
                    'id'           => $this->classeGrille->id,
                    'coefficient'  => $this->classeGrille->coefficient,
                    'categorie'    => $this->classeGrille->categorie?->nom,
                    'categorie_id' => $this->classeGrille->categorie_id,
                    'grade'        => $this->classeGrille->grade?->nom,
                    'grade_id'     => $this->classeGrille->grade_id,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
