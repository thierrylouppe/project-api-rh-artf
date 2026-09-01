<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeCongeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'agent_id'            => $this->agent_id,
            'agent'               => new AgentResource($this->whenLoaded('agent')),
            'type_conge_id'       => $this->type_conge_id,
            'type_conge'          => new TypeCongeResource($this->whenLoaded('typeConge')),
            'date_debut'          => $this->date_debut?->format('Y-m-d'),
            'date_fin'            => $this->date_fin?->format('Y-m-d'),
            'nb_jours'            => $this->nb_jours,
            'motif'               => $this->motif,
            'statut'              => $this->statut?->value,
            'statut_label'        => $this->statut?->label(),
            'commentaire_n1'      => $this->commentaire_n1,
            'commentaire_rh'      => $this->commentaire_rh,
            'commentaire_dg'      => $this->commentaire_dg,
            'date_validation_n1'  => $this->date_validation_n1,
            'date_validation_rh'  => $this->date_validation_rh,
            'date_validation_dg'  => $this->date_validation_dg,
            'prochaine_etape'     => $this->whenLoaded('typeConge', fn () => $this->typeConge->prochaineEtape($this->statut)),
            'justificatif'        => $this->justificatif_path ? [
                'nom' => $this->justificatif_nom_original,
            ] : null,
            'created_at'          => $this->created_at,
        ];
    }
}
