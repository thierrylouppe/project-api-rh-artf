<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiplomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $classe = $this->whenLoaded('classeGrille');

        return [
            'id'                        => $this->id,
            'nom'                       => $this->nom,
            'sigle'                     => $this->sigle ?? null,
            'description'               => $this->description,
            'classegrillesalariale_id'  => $this->classegrillesalariale_id,
            'classe_grille'             => $classe ? [
                'id'          => $this->classeGrille->id,
                'coefficient' => $this->classeGrille->coefficient,
                'categorie'   => $this->classeGrille->categorie?->nom,
                'categorie_id'=> $this->classeGrille->categorie_id,
                'grade'       => $this->classeGrille->grade?->nom,
                'grade_id'    => $this->classeGrille->grade_id,
            ] : null,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
