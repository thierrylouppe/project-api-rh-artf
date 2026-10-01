<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'libelle'       => $this->libelle,
            'type_critere'  => $this->type_critere,
            'bareme_max'    => $this->bareme_max,
            'ordre'         => $this->ordre,
            'actif'         => $this->actif,
            'created_at'    => $this->created_at?->toDateTimeString(),
        ];
    }
}
