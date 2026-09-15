<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReclassementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'agent_id'            => $this->agent_id,
            'agent'               => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'type'                => $this->type->value,
            'type_label'          => $this->type->label(),
            'article'             => $this->type->article(),
            'statut'              => $this->statut->value,
            'statut_label'        => $this->statut->label(),
            'prochaine_etape'     => $this->prochaine_etape ?? $this->prochaineEtapeDefaut(),
            'classe_origine'      => $this->whenLoaded('classeOrigine', fn () => $this->classeResume($this->classeOrigine)),
            'classe_cible'        => $this->whenLoaded('classeCible', fn () => $this->classeResume($this->classeCible)),
            'fonction_cible'      => $this->whenLoaded('fonctionCible', fn () => $this->fonctionCible ? [
                'id'  => $this->fonctionCible->id,
                'nom' => $this->fonctionCible->nom,
            ] : null),
            'diplome_id'          => $this->diplome_id,
            'diplome'             => $this->whenLoaded('diplome', fn () => $this->diplome ? [
                'id'    => $this->diplome->id,
                'nom'   => $this->diplome->nom,
                'sigle' => $this->diplome->sigle,
            ] : null),
            'motif'               => $this->motif,
            'motif_reconversion'  => $this->motif_reconversion?->value,
            'piece_path'          => $this->piece_path,
            'age_ans'             => $this->age_ans,
            'anciennete_ans'      => $this->anciennete_ans,
            'annees_dans_classe'  => $this->annees_dans_classe,
            'echelon_origine'     => $this->echelon_origine,
            'echelon_cible'       => $this->echelon_cible,
            'eligibilite'         => $this->when(isset($this->eligibilite), $this->eligibilite),
            'valide_at'           => $this->valide_at?->toDateTimeString(),
            'applique_at'         => $this->applique_at?->toDateTimeString(),
            'created_at'          => $this->created_at?->toDateTimeString(),
        ];
    }

    private function prochaineEtapeDefaut(): ?string
    {
        return match ($this->statut->value) {
            'soumis'   => 'approuver',
            'approuve' => 'appliquer',
            default    => null,
        };
    }

    private function classeResume($classe): ?array
    {
        if (! $classe) {
            return null;
        }

        return [
            'id'          => $classe->id,
            'categorie'   => $classe->categorie?->nom,
            'grade'       => $classe->grade?->nom,
            'coefficient' => $classe->coefficient,
        ];
    }
}
