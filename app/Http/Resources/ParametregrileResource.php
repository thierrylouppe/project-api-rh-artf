<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParametregrileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'valeur_point_indice'  => $this->valeur_point_indice,
            'indice_base'          => $this->indice_base,
            'echelon_depart'       => $this->echelon_depart,
            'echelon_fin'          => $this->echelon_fin,
            'ecart_depart'         => $this->ecart_depart,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
