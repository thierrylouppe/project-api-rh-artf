<?php

namespace App\Services;

use App\Enums\StatutBonification;
use App\Interfaces\AvancementExceptionnelInterface;
use App\Models\AvancementExceptionnel;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Avancement exceptionnel (CCN ARTF art. 72).
 * Commission d'avancement, sur proposition DG, ≤ 2 échelons.
 *
 * Workflow :
 *   1. DG propose l'avancement exceptionnel pour un agent.
 *   2. La commission approuve ou rejette.
 *   3. RH applique l'échelon en paie → `appliquer()` idempotent.
 */
class AvancementExceptionnelService extends BaseService
{
    public function __construct(
        AvancementExceptionnelInterface     $repository,
        private readonly SalaireAgentService $salaireService,
    ) {
        parent::__construct($repository);
    }

    public function getEnAttente(): \Illuminate\Support\Collection
    {
        return $this->repository->enAttente();
    }

    /**
     * Proposer un avancement exceptionnel (DG).
     *
     * @throws ValidationException si nb_echelons > 2
     */
    public function proposer(int $agentId, int $nbEchelons, string $motif, User $dg, array $data = []): AvancementExceptionnel
    {
        if ($nbEchelons < 1 || $nbEchelons > 2) {
            throw ValidationException::withMessages([
                'nb_echelons' => 'Le nombre d\'échelons doit être 1 ou 2 (art. 72).',
            ]);
        }

        return $this->repository->create([
            'agent_id'                => $agentId,
            'commission_avancement_id' => $data['commission_avancement_id'] ?? null,
            'nb_echelons'             => $nbEchelons,
            'motif'                   => $motif,
            'propose_par'             => $dg->id,
            'date_proposition'        => $data['date_proposition'] ?? now()->toDateString(),
            'statut'                  => StatutBonification::EN_ATTENTE->value,
        ]);
    }

    /**
     * Approuver ou rejeter la proposition.
     *
     * @throws ValidationException si déjà traitée
     */
    public function traiter(int $id, User $commission, bool $approuver, ?string $commentaire = null): AvancementExceptionnel
    {
        /** @var AvancementExceptionnel $avan */
        $avan = $this->repository->findById($id);

        if ($avan->statut->estTraitee()) {
            throw ValidationException::withMessages([
                'statut' => 'Cette proposition est déjà traitée.',
            ]);
        }

        return $this->repository->update($id, [
            'statut'      => $approuver ? StatutBonification::APPROUVEE->value : StatutBonification::REJETEE->value,
            'commentaire' => $commentaire,
            'traite_par'  => $commission->id,
            'traite_le'   => now(),
        ]);
    }

    /**
     * Appliquer l'avancement exceptionnel en paie (idempotent).
     *
     * @throws ValidationException si non approuvé ou déjà appliqué
     */
    public function appliquer(int $id, User $rh): array
    {
        /** @var AvancementExceptionnel $avan */
        $avan = $this->repository->findById($id);

        if ($avan->estApplique()) {
            return ['avance' => false, 'message' => 'Avancement exceptionnel déjà appliqué (idempotent).'];
        }

        if ($avan->statut !== StatutBonification::APPROUVEE) {
            throw ValidationException::withMessages([
                'statut' => 'Seuls les avancements exceptionnels approuvés peuvent être appliqués.',
            ]);
        }

        $this->salaireService->avancerEchelons($avan->agent_id, $avan->nb_echelons, 'Avancement exceptionnel art. 72');

        $this->repository->update($id, [
            'applique_le'  => now(),
            'applique_par' => $rh->id,
        ]);

        return [
            'avance'  => true,
            'message' => "+{$avan->nb_echelons} échelon(s) appliqué(s) — avancement exceptionnel art. 72.",
        ];
    }
}
