<?php

namespace App\Services;

use App\Enums\StatutBonification;
use App\Interfaces\BonificationStageInterface;
use App\Models\BonificationStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Bonification de +2 échelons après stage ≥ 9 mois (CCN ARTF art. 71).
 * Parcours séparé du cycle d'évaluation 24 mois.
 *
 * Workflow :
 *   1. Agent (ou RH) soumet la demande avec les dates du stage et la pièce justificative.
 *   2. RH/DG approuve ou rejette.
 *   3. Si approuvée, RH applique l'échelon en paie → `appliquer()` idempotent.
 */
class BonificationStageService extends BaseService
{
    public function __construct(
        BonificationStageInterface          $repository,
        private readonly SalaireAgentService $salaireService,
    ) {
        parent::__construct($repository);
    }

    public function getEnAttente(): \Illuminate\Support\Collection
    {
        return $this->repository->enAttente();
    }

    /**
     * Soumettre une demande de bonification de stage.
     *
     * @throws ValidationException si durée < 9 mois
     */
    public function soumettre(int $agentId, array $data, User $user): BonificationStage
    {
        $debut   = Carbon::parse($data['date_debut_stage']);
        $fin     = Carbon::parse($data['date_fin_stage']);
        $duree   = (int) $debut->diffInMonths($fin);

        if ($duree < 9) {
            throw ValidationException::withMessages([
                'duree_mois' => "La durée du stage est de {$duree} mois. Le minimum requis est de 9 mois (art. 71).",
            ]);
        }

        return $this->repository->create([
            'agent_id'           => $agentId,
            'date_debut_stage'   => $debut->toDateString(),
            'date_fin_stage'     => $fin->toDateString(),
            'duree_mois'         => $duree,
            'type_document'      => $data['type_document'],
            'reference_document' => $data['reference_document'] ?? null,
            'nb_echelons'        => 2, // toujours 2 selon art. 71
            'statut'             => StatutBonification::EN_ATTENTE->value,
            'created_by'         => $user->id,
        ]);
    }

    /**
     * Approuver ou rejeter une demande.
     *
     * @throws ValidationException si déjà traitée
     */
    public function traiter(int $id, User $rh, bool $approuver, ?string $commentaire = null): BonificationStage
    {
        /** @var BonificationStage $bonif */
        $bonif = $this->repository->findById($id);

        if ($bonif->statut->estTraitee()) {
            throw ValidationException::withMessages([
                'statut' => 'Cette demande est déjà traitée.',
            ]);
        }

        return $this->repository->update($id, [
            'statut'     => $approuver ? StatutBonification::APPROUVEE->value : StatutBonification::REJETEE->value,
            'commentaire' => $commentaire,
            'traite_par' => $rh->id,
            'traite_le'  => now(),
        ]);
    }

    /**
     * Appliquer la bonification en paie (idempotent).
     * Appelle `SalaireAgentService::avancerEchelons(2)`.
     *
     * @throws ValidationException si non approuvée ou déjà appliquée
     */
    public function appliquer(int $id, User $rh): array
    {
        /** @var BonificationStage $bonif */
        $bonif = $this->repository->findById($id);

        if ($bonif->estAppliquee()) {
            return ['avance' => false, 'message' => 'Bonification déjà appliquée (idempotent).'];
        }

        if ($bonif->statut !== StatutBonification::APPROUVEE) {
            throw ValidationException::withMessages([
                'statut' => 'Seules les demandes approuvées peuvent être appliquées.',
            ]);
        }

        // Avancement en paie
        $this->salaireService->avancerEchelons($bonif->agent_id, $bonif->nb_echelons, 'Bonification stage art. 71');

        $this->repository->update($id, [
            'applique_le' => now(),
            'applique_par' => $rh->id,
        ]);

        return [
            'avance'  => true,
            'message' => "+{$bonif->nb_echelons} échelon(s) appliqué(s) — bonification stage art. 71.",
        ];
    }
}
