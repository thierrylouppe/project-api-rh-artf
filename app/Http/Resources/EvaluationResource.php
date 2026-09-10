<?php

namespace App\Http\Resources;

use App\Http\Resources\AvisHierarchiqueResource;
use App\Http\Resources\ReclamationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'session_id'     => $this->session_id,
            'session'        => $this->whenLoaded('session', fn () => new SessionEvaluationResource($this->session)),
            'agent_id'       => $this->agent_id,
            'agent'          => $this->whenLoaded('agent', fn () => new AgentIdentiteResource($this->agent)),
            'superieur_id'   => $this->superieur_id,
            'superieur'      => $this->whenLoaded('superieur', fn () => new AgentIdentiteResource($this->superieur)),
            'date_evaluation'               => $this->date_evaluation?->toDateString(),
            'jours_absence_non_justifiee'   => $this->jours_absence_non_justifiee,
            'sanctions'                     => $this->sanctions,
            'avis_superieur'                => $this->avis_superieur,
            'note_globale'                  => $this->note_globale,
            'mention'                       => $this->mention,
            // Phase 4 — Commission préparatoire
            'commission_note'               => $this->commission_note,
            'note_synthese'                 => $this->note_synthese,
            // Phase 4 — Commission d'avancement
            'commission_decision'           => $this->commission_decision?->value,
            'commission_decision_label'     => $this->commission_decision?->label(),
            'nombre_echelons'               => $this->nombre_echelons,
            'note_avancement'               => $this->note_avancement,
            'echelon_avance'                => $this->echelon_avance,
            'statut'                        => $this->statut->value,
            'statut_label'                  => $this->statut->label(),
            'prochaine_etape'               => $this->prochainEtape(),
            'signe_par_evaluateur_at'       => $this->signe_par_evaluateur_at?->toDateTimeString(),
            'signe_par_evalue_at'           => $this->signe_par_evalue_at?->toDateTimeString(),
            'date_validation_rh'            => $this->date_validation_rh?->toDateTimeString(),
            'commentaire_rh'                => $this->commentaire_rh,
            'conforme_rh'                   => $this->conforme_rh,
            // réclamation (chargée seulement sur show)
            'reclamation'           => $this->whenLoaded('reclamation', fn () => new ReclamationResource($this->reclamation)),
            // avis hiérarchiques (chargés seulement sur show)
            'avis_hierarchiques'    => $this->whenLoaded('avisHierarchiques', fn () => AvisHierarchiqueResource::collection($this->avisHierarchiques)),
            // notes du critère (chargées seulement sur show)
            'notes'                 => $this->whenLoaded('notes', fn () => NoteEvaluationResource::collection($this->notes)),
            'created_at'            => $this->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Indique la prochaine action attendue selon le statut.
     * Aide le FE à n'afficher que les boutons pertinents.
     */
    private function prochainEtape(): ?string
    {
        return match ($this->statut->value) {
            'en_attente'        => 'noter',
            'en_cours'          => 'continuer_notation',
            'notee'             => 'avis_et_signer',
            'signee_evaluateur' => 'signer_evalue',
            'signee_evalue'     => 'envoyer_rh',
            'en_reclamation'    => 'traiter_reclamation',
            'en_validation_rh'  => 'valider_rh',
            'finalisee'         => $this->nextEtapeApresFinalisee(),
            'rejetee'           => 'corriger_notation',
            default             => null, // annulee
        };
    }

    private function nextEtapeApresFinalisee(): ?string
    {
        if (! $this->commission_decision) {
            return 'commission_preparatoire';
        }
        if ($this->commission_decision->value === 'favorable' && ! $this->echelon_avance) {
            return 'avancer_echelon';
        }
        return null;
    }
}
