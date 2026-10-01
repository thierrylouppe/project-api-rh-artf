<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SessionEvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'debut_session'  => $this->debut_session?->toDateString(),
            'fin_session'    => $this->fin_session?->toDateString(),
            'statut'         => $this->statut->value,
            'statut_label'   => $this->statut->label(),
            'type_annee'     => $this->type_annee,
            'semestre'       => $this->semestre,
            'description'    => $this->description,
            'cloturee_at'    => $this->cloturee_at?->toDateTimeString(),
            // compteurs (eager-loaded via withCount)
            'nb_fiches_total'      => $this->whenCounted('evaluations'),
            'nb_fiches_finalisees' => $this->when(
                isset($this->evaluations_finalisees_count),
                fn () => $this->evaluations_finalisees_count,
            ),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
