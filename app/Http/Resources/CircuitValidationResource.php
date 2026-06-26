<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CircuitValidationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'type_integration_id' => $this->type_integration_id,
            'niveau'              => $this->niveau->value,
            'niveau_label'        => $this->niveau->label(),
            'ordre'               => $this->ordre,
            'actif'               => $this->actif,
        ];
    }
}
