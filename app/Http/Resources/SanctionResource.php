<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SanctionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $peutVoirNotes = $request->user()?->hasPermissionTo('consulter-discipline', 'api')
            || $request->user()?->hasPermissionTo('gerer-discipline', 'api');

        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'agent' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent ? new AgentIdentiteResource($this->agent) : null
            ),
            'type_sanction_id' => $this->type_sanction_id,
            'type_sanction' => new TypeSanctionResource($this->whenLoaded('typeSanction')),
            'motif' => $this->motif,
            'date_faits' => $this->date_faits?->format('Y-m-d'),
            'nb_jours' => $this->nb_jours,
            'avec_indemnite' => $this->avec_indemnite,
            'date_debut_effet' => $this->date_debut_effet?->format('Y-m-d'),
            'date_fin_effet' => $this->date_fin_effet?->format('Y-m-d'),
            'conservee_jusqu_au' => $this->conservee_jusqu_au?->format('Y-m-d'),
            'dans_delai_conservation' => $this->conservee_jusqu_au
                ? $this->conservee_jusqu_au->gte(now()->startOfDay())
                : null,
            'recidive' => $this->when(
                array_key_exists('recidive', $this->resource->getAttributes()),
                fn () => (bool) $this->recidive
            ),
            'antecedents_5_ans' => $this->when(
                array_key_exists('antecedents_5_ans', $this->resource->getAttributes()),
                fn () => $this->antecedents_5_ans
            ),
            'notes_instruction' => $this->when($peutVoirNotes, $this->notes_instruction),
            'date_decision' => $this->date_decision?->format('Y-m-d'),
            'decision' => $this->decision,
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'prochaine_etape' => $this->statut?->prochaineEtape(),
            'commentaire_validation' => $this->commentaire_validation,
            'created_by' => $this->created_by,
            'createur' => $this->when(
                $this->relationLoaded('createur'),
                fn () => $this->createur ? [
                    'id' => $this->createur->id,
                    'name' => $this->createur->name,
                ] : null
            ),
            'validateur_id' => $this->validateur_id,
            'validateur' => $this->when(
                $this->relationLoaded('validateur'),
                fn () => $this->validateur ? [
                    'id' => $this->validateur->id,
                    'name' => $this->validateur->name,
                ] : null
            ),
            'pieces' => $this->when(
                $this->relationLoaded('pieces'),
                fn () => SanctionPieceResource::collection($this->pieces)
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
