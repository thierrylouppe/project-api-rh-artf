<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvisHierarchiqueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'evaluation_id'  => $this->evaluation_id,
            'niveau'         => $this->niveau->value,
            'niveau_label'   => $this->niveau->label(),
            'ordre'          => $this->ordre,
            'avis'           => $this->avis,
            'approuve'       => $this->approuve,
            'observations'   => $this->observations,
            'signe'          => $this->signe,
            'date_signature' => $this->date_signature?->toDateTimeString(),
            'signe_par'      => $this->whenLoaded('signePar', fn () => [
                'id'   => $this->signePar?->id,
                'name' => $this->signePar?->name,
            ]),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
