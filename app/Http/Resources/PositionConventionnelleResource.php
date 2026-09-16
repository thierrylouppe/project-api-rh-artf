<?php

namespace App\Http\Resources;

use App\Enums\StatutPositionConventionnelle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionConventionnelleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'agent_id'             => $this->agent_id,
            'agent'                => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'type'                 => $this->type->value,
            'type_label'           => $this->type->label(),
            'article'              => $this->type->article(),
            'statut'               => $this->statut->value,
            'statut_label'         => $this->statut->label(),
            'prochaine_etape'      => $this->prochaine_etape ?? $this->prochaineEtapeDefaut(),
            'peut_renouveler'      => $this->statut === StatutPositionConventionnelle::ACTIVE
                && $this->type->peutRenouveler((int) $this->nb_renouvellements),
            'date_debut'           => $this->date_debut?->format('Y-m-d'),
            'date_fin'             => $this->date_fin?->format('Y-m-d'),
            'organisme_accueil'    => $this->organisme_accueil,
            'consentement_agent'   => (bool) $this->consentement_agent,
            'detachement_office'   => (bool) $this->detachement_office,
            'nb_renouvellements'   => (int) $this->nb_renouvellements,
            'commentaire'          => $this->commentaire,
            'piece_path'           => $this->piece_path,
            'coupe_remuneration'   => $this->type->coupeRemuneration(),
            'reintegration'        => $this->when(isset($this->reintegration), $this->reintegration),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }

    private function prochaineEtapeDefaut(): ?string
    {
        return match ($this->statut) {
            StatutPositionConventionnelle::SOUMISE => 'approuver',
            StatutPositionConventionnelle::ACTIVE  => 'cloturer',
            default                                => null,
        };
    }
}
