<?php

namespace App\Services;

use App\Enums\StatutEvaluation;
use App\Enums\StatutReclamation;
use App\Interfaces\EvaluationInterface;
use App\Interfaces\ReclamationInterface;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Gère le cycle de vie d'une réclamation (CCN art. 65).
 *
 * Workflow :
 *   Agent dépose → en_attente
 *   RH accepte   → acceptee  (renvoi au notateur : fiche rejetée → retour en_cours)
 *   RH rejette   → rejetee   (maintien de la note : envoi en_validation_rh)
 */
class ReclamationService extends BaseService
{
    public function __construct(
        ReclamationInterface               $repository,
        private readonly EvaluationInterface $evaluationRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Traitement RH d'une réclamation.
     *
     * @param bool $acceptee true = acceptée (renvoi notateur), false = rejetée (maintien)
     * @throws ValidationException
     */
    public function traiter(int $reclamationId, User $rh, bool $acceptee, ?string $commentaire = null): Reclamation
    {
        /** @var Reclamation $reclamation */
        $reclamation = $this->repository->findById($reclamationId);

        if ($reclamation->statut->estTraitee()) {
            throw ValidationException::withMessages([
                'statut' => "Cette réclamation a déjà été traitée ({$reclamation->statut->label()}).",
            ]);
        }

        $nouveauStatut = $acceptee ? StatutReclamation::ACCEPTEE : StatutReclamation::REJETEE;

        $reclamation = $this->repository->update($reclamationId, [
            'statut'        => $nouveauStatut->value,
            'commentaire_rh' => $commentaire,
            'traite_par'    => $rh->id,
            'traite_le'     => now(),
        ]);

        // Cascade sur la fiche
        $evaluation = $this->evaluationRepository->findById($reclamation->evaluation_id);

        if ($acceptee) {
            // Acceptée : renvoi au notateur pour correction (retour en_cours)
            $this->evaluationRepository->update($evaluation->id, [
                'statut' => StatutEvaluation::EN_COURS->value,
            ]);
        } else {
            // Rejetée : note maintenue, envoi en validation RH
            $this->evaluationRepository->update($evaluation->id, [
                'statut' => StatutEvaluation::EN_VALIDATION_RH->value,
            ]);
        }

        return $reclamation->fresh(['agent']);
    }

    /** Réclamations en attente de traitement. */
    public function enAttente(): Collection
    {
        return $this->repository->getAll()
            ->filter(fn (Reclamation $r) => $r->statut === StatutReclamation::EN_ATTENTE);
    }
}
