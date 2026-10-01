<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriseEnChargeResource extends JsonResource
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
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'article_ccn' => $this->type?->articleCcn(),
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'prochaine_etape' => $this->statut?->prochaineEtape(),
            'date_soins' => $this->date_soins?->format('Y-m-d'),
            'ayant_droit_id' => $this->ayant_droit_id,
            'structure_sanitaire_id' => $this->structure_sanitaire_id,
            'structure' => $this->when(
                $this->relationLoaded('structure'),
                fn () => $this->structure ? [
                    'id' => $this->structure->id,
                    'nom' => $this->structure->nom,
                    'type' => $this->structure->type?->value,
                ] : null
            ),
            'montant_facture' => $this->montant_facture,
            'montant_calcule' => $this->montant_calcule,
            'montant_accorde' => $this->montant_accorde,
            'calcul_snapshot' => $this->calcul_snapshot,
            'date_debut' => $this->date_debut?->format('Y-m-d'),
            'date_fin' => $this->date_fin?->format('Y-m-d'),
            'lieu' => $this->lieu,
            'at_mp' => (bool) $this->at_mp,
            'notes_instruction' => $this->notes_instruction,
            'commentaire_decision' => $this->commentaire_decision,
            'date_decision' => $this->date_decision?->format('Y-m-d'),
            'paie_annee' => $this->paie_annee,
            'paie_mois' => $this->paie_mois,
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
