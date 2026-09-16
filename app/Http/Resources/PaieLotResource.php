<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaieLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $anomalies = $this->anomalies ?? [];

        return [
            'id' => $this->id,
            'annee' => $this->annee,
            'mois' => $this->mois,
            'periode' => sprintf('%04d-%02d', $this->annee, $this->mois),
            'statut' => $this->statut?->value,
            'statut_label' => $this->statut?->label(),
            'commentaire' => $this->commentaire,
            'anomalies' => $anomalies,
            'nb_anomalies' => count($anomalies),
            'nb_anomalies_bloquantes' => $this->nbAnomaliesBloquantes(),
            'total_gains' => (float) $this->total_gains,
            'total_retenues' => (float) $this->total_retenues,
            'total_net' => (float) $this->total_net,
            'nb_lignes' => (int) $this->nb_lignes,
            'generated_at' => $this->generated_at,
            'generated_by' => $this->generated_by,
            'controle_at' => $this->controle_at,
            'controle_par' => $this->controle_par,
            'valide_at' => $this->valide_at,
            'valide_par' => $this->valide_par,
            'cloture_at' => $this->cloture_at,
            'cloture_par' => $this->cloture_par,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
