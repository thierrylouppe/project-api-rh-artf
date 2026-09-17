<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArretSanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'agent' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent ? new AgentIdentiteResource($this->agent) : null
            ),
            'nature' => $this->nature?->value,
            'nature_label' => $this->nature?->label(),
            'article_ccn' => $this->nature?->articleCcn(),
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'prochaine_etape' => $this->statut?->prochaineEtape(),
            'date_fait' => $this->date_fait?->format('Y-m-d'),
            'date_notification' => $this->date_notification?->format('Y-m-d'),
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'alerte_72h' => (bool) $this->alerte_72h,
            'structure_sanitaire_id' => $this->structure_sanitaire_id,
            'demande_conge_id' => $this->demande_conge_id,
            'nb_mois' => $this->nb_mois,
            'nb_mois_majoration' => $this->nb_mois_majoration,
            'montant_mensuel' => $this->montant_mensuel,
            'montant_mensuel_demi' => $this->montant_mensuel_demi,
            'calcul_snapshot' => $this->calcul_snapshot,
            'notes_instruction' => $this->notes_instruction,
            'commentaire_decision' => $this->commentaire_decision,
            'date_decision' => $this->date_decision?->format('Y-m-d'),
            'paie_element_affectation_id' => $this->paie_element_affectation_id,
            'pieces' => $this->when(
                $this->relationLoaded('pieces'),
                fn () => PieceSanteResource::collection($this->pieces)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
