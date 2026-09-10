<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'session_id'     => $this->session_id,
            'session'        => $this->whenLoaded('session', fn () => new SessionEvaluationResource($this->session)),
            'agent_id'       => $this->agent_id,
            'agent'          => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'superieur_id'   => $this->superieur_id,
            'superieur'      => $this->whenLoaded('superieur', fn () => new AgentIdentiteResource($this->superieur)),
            'date_evaluation'               => $this->date_evaluation?->toDateString(),
            'jours_absence_non_justifiee'   => $this->jours_absence_non_justifiee,
            'sanctions'                     => $this->sanctions,
            'avis_superieur'                => $this->avis_superieur,
            'note_globale'                  => $this->note_globale,
            'mention'                       => $this->mention,
            'statut'                        => $this->statut->value,
            'statut_label'                  => $this->statut->label(),
            'signe_par_evaluateur_at'       => $this->signe_par_evaluateur_at?->toDateTimeString(),
            'signe_par_evalue_at'           => $this->signe_par_evalue_at?->toDateTimeString(),
            'date_validation_rh'            => $this->date_validation_rh?->toDateTimeString(),
            'commentaire_rh'                => $this->commentaire_rh,
            'conforme_rh'                   => $this->conforme_rh,
            // notes du critère (chargées seulement sur show)
            'notes'          => $this->whenLoaded('notes', fn () => NoteEvaluationResource::collection($this->notes)),
            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
