<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrestationResource extends JsonResource
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
            'date_fait' => $this->date_fait?->format('Y-m-d'),
            'ayant_droit_id' => $this->ayant_droit_id,
            'ayant_droit' => $this->when(
                $this->relationLoaded('ayantDroit'),
                fn () => $this->ayantDroit ? [
                    'id' => $this->ayantDroit->id,
                    'nom' => $this->ayantDroit->nom,
                    'prenom' => $this->ayantDroit->prenom,
                    'type' => $this->ayantDroit->type?->value,
                ] : null
            ),
            'beneficiaire_libelle' => $this->beneficiaire_libelle,
            'transport_corps' => (bool) $this->transport_corps,
            'montant_demande' => $this->montant_demande,
            'montant_calcule' => $this->montant_calcule,
            'montant_accorde' => $this->montant_accorde,
            'calcul_snapshot' => $this->calcul_snapshot,
            'notes_instruction' => $this->notes_instruction,
            'commentaire_decision' => $this->commentaire_decision,
            'date_decision' => $this->date_decision?->format('Y-m-d'),
            'paie_annee' => $this->paie_annee,
            'paie_mois' => $this->paie_mois,
            'paie_element_affectation_id' => $this->paie_element_affectation_id,
            'created_by' => $this->created_by,
            'createur' => $this->when(
                $this->relationLoaded('createur'),
                fn () => $this->createur ? [
                    'id' => $this->createur->id,
                    'name' => $this->createur->name,
                ] : null
            ),
            'instruite_by' => $this->instruite_by,
            'instructeur' => $this->when(
                $this->relationLoaded('instructeur'),
                fn () => $this->instructeur ? [
                    'id' => $this->instructeur->id,
                    'name' => $this->instructeur->name,
                ] : null
            ),
            'decideur_id' => $this->decideur_id,
            'decideur' => $this->when(
                $this->relationLoaded('decideur'),
                fn () => $this->decideur ? [
                    'id' => $this->decideur->id,
                    'name' => $this->decideur->name,
                ] : null
            ),
            'pieces' => $this->when(
                $this->relationLoaded('pieces'),
                fn () => PrestationPieceResource::collection($this->pieces)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
