<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassegrillesalarialeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'coefficient' => $this->coefficient,
            'categorie'   => $this->whenLoaded('categorie', fn () => [
                'id'    => $this->categorie->id,
                'nom'   => $this->categorie->nom,
                'sigle' => $this->categorie->sigle,
            ]),
            'grade'       => $this->whenLoaded('grade', fn () => [
                'id'     => $this->grade->id,
                'nom'    => $this->grade->nom,
                'niveau' => $this->grade->niveau,
            ]),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
