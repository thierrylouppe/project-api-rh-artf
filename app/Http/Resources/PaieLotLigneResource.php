<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaieLotLigneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lot_id' => $this->lot_id,
            'agent_id' => $this->agent_id,
            'agent' => $this->when(
                $this->relationLoaded('agent'),
                fn () => $this->agent ? [
                    'id' => $this->agent->id,
                    'matricule' => $this->agent->matricule,
                    'nom' => $this->agent->nom,
                    'prenom' => $this->agent->prenom,
                    'nom_complet' => $this->agent->nom_complet,
                    'statut' => $this->agent->statut,
                ] : null
            ),
            'salaire_agent_id' => $this->salaire_agent_id,
            'hors_grille' => (bool) $this->hors_grille,
            'montant_base' => (float) $this->montant_base,
            'total_gains' => (float) $this->total_gains,
            'total_retenues' => (float) $this->total_retenues,
            'montant_net' => (float) $this->montant_net,
            'nb_anomalies' => (int) $this->nb_anomalies,
            'snapshot_agent' => $this->snapshot_agent,
            'details' => $this->when(
                $this->relationLoaded('details'),
                fn () => $this->details->map(fn ($detail) => [
                    'id' => $detail->id,
                    'paie_element_id' => $detail->paie_element_id,
                    'code' => $detail->code,
                    'libelle' => $detail->libelle,
                    'nature' => $detail->nature?->value ?? $detail->nature,
                    'sens' => $detail->sens?->value ?? $detail->sens,
                    'montant' => (float) $detail->montant,
                    'source' => $detail->source?->value ?? $detail->source,
                    'source_label' => $detail->source?->label(),
                    'meta' => $detail->meta,
                ])->values()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
