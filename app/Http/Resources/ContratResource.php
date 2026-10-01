<?php

namespace App\Http\Resources;

use App\Enums\StatutEssai;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContratResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $agent = $this->whenLoaded('agent', fn () => $this->agent, null);

        return [
            'id'                     => $this->id,
            'agent_id'               => $this->when(! $this->relationLoaded('agent'), $this->agent_id),
            'agent'                  => new AgentIdentiteResource($this->whenLoaded('agent')),
            'type_contrat_id'        => $this->when(! $this->relationLoaded('typeContrat'), $this->type_contrat_id),
            'type_contrat'           => new TypeContratResource($this->whenLoaded('typeContrat')),
            'fonction_id'            => $this->when(! $this->relationLoaded('fonction'), $this->fonction_id),
            'fonction'               => new FonctionResource($this->whenLoaded('fonction')),
            'date_debut'             => $this->date_debut?->format('Y-m-d'),
            'date_fin'               => $this->date_fin?->format('Y-m-d'),
            'remuneration'           => $this->remuneration,
            'statut'                 => $this->statut,
            'dossier_integration_id' => $this->dossier_integration_id,
            'lieu_recrutement'       => $this->lieu_recrutement,
            'essai'                  => [
                'statut'              => $this->statut_essai?->value,
                'statut_label'        => $this->statut_essai?->label(),
                'duree_mois'          => $this->duree_essai_mois,
                'date_debut'          => $this->date_debut_essai?->format('Y-m-d'),
                'date_fin'            => $this->date_fin_essai?->format('Y-m-d'),
                'renouvele'           => (bool) $this->essai_renouvele,
                'date_confirmation'   => $this->date_confirmation_essai?->format('Y-m-d'),
                'prochaine_etape'     => $this->prochaineEtapeEssai(),
                'peut_renouveler'     => $this->peutRenouvelerEssai(),
            ],
            'mentions'               => $agent ? [
                'noms'                    => $agent->nom_complet,
                'nationalite'             => $agent->nationalite,
                'date_naissance'          => $agent->date_naissance?->format('Y-m-d'),
                'lieu_naissance'          => $agent->lieu_naissance,
                'sexe'                    => $agent->genre,
                'situation_matrimoniale'  => $agent->relationLoaded('situationFamiliale')
                    ? $agent->situationFamiliale?->statut_matrimonial
                    : null,
                'date_recrutement'        => $this->date_debut?->format('Y-m-d'),
                'lieu_recrutement'        => $this->lieu_recrutement,
                'essai'                   => $this->statut_essai === StatutEssai::NON_APPLICABLE ? null : [
                    'duree_mois' => $this->duree_essai_mois,
                    'date_debut' => $this->date_debut_essai?->format('Y-m-d'),
                    'date_fin'   => $this->date_fin_essai?->format('Y-m-d'),
                ],
                'emploi'                  => $this->emploiMention($agent),
                'remuneration'            => $this->remuneration,
                'lieu_travail'            => $this->lieuTravailMention($agent),
            ] : null,
            'created_at'             => $this->created_at,
            'updated_at'             => $this->updated_at,
        ];
    }

    private function emploiMention($agent): ?string
    {
        if ($this->relationLoaded('fonction') && $this->fonction) {
            return $this->fonction->nom;
        }

        if ($agent->relationLoaded('fonction')) {
            return $agent->fonction?->nom;
        }

        return null;
    }

    private function lieuTravailMention($agent): ?string
    {
        if (! $agent->relationLoaded('affectationActive') || $agent->affectationActive === null) {
            return null;
        }

        $affectation = $agent->affectationActive;
        if (! $affectation->relationLoaded('structure') || $affectation->structure === null) {
            return null;
        }

        return $affectation->structure->nom ?? null;
    }
}
