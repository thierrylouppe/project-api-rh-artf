<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'echelon' => $this->echelon,
            'indice'  => $this->indice,
            'salaire' => $this->salaire,
            'classe'  => $this->whenLoaded('classe', fn () => [
                'id'          => $this->classe->id,
                'coefficient' => $this->classe->coefficient,
                'categorie'   => [
                    'id'    => $this->classe->categorie->id,
                    'nom'   => $this->classe->categorie->nom,
                    'sigle' => $this->classe->categorie->sigle,
                ],
                'grade'       => [
                    'id'     => $this->classe->grade->id,
                    'nom'    => $this->classe->grade->nom,
                    'niveau' => $this->classe->grade->niveau,
                ],
            ]),
        ];
    }
}
