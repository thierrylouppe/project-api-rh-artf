<?php

namespace App\Services;

use App\Interfaces\CongeSoldeInterface;
use App\Interfaces\RegleAcquisitionCongeInterface;
use App\Interfaces\TypeCongeInterface;
use App\Models\CongeSolde;
use Illuminate\Support\Collection;

class CongeSoldeService extends BaseService
{
    public function __construct(
        CongeSoldeInterface $repository,
        private readonly RegleAcquisitionCongeInterface $regleRepository,
        private readonly TypeCongeInterface $typeCongeRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId, ?int $annee = null): Collection
    {
        return $this->repository->getByAgent($agentId, $annee);
    }

    public function getOrCreate(int $agentId, int $typeCongeId, int $annee): CongeSolde
    {
        $existant = $this->repository->findFor($agentId, $typeCongeId, $annee);
        if ($existant) {
            return $existant;
        }

        $initial = $this->soldeInitial($typeCongeId);

        return $this->repository->create([
            'agent_id'       => $agentId,
            'type_conge_id'  => $typeCongeId,
            'annee'          => $annee,
            'solde_initial'  => $initial,
            'solde_actuel'   => $initial,
        ]);
    }

    public function verifierSolde(int $agentId, int $typeCongeId, int $annee, int $jours): CongeSolde
    {
        $solde = $this->getOrCreate($agentId, $typeCongeId, $annee);

        abort_if(
            (float) $solde->solde_actuel < $jours,
            422,
            "Solde insuffisant ({$solde->solde_actuel} j. disponibles, {$jours} j. demandés)."
        );

        return $solde;
    }

    public function debiter(int $agentId, int $typeCongeId, int $annee, int $jours): CongeSolde
    {
        $solde = $this->verifierSolde($agentId, $typeCongeId, $annee, $jours);

        return $this->repository->update($solde->id, [
            'solde_actuel' => (float) $solde->solde_actuel - $jours,
        ]);
    }

    private function soldeInitial(int $typeCongeId): float
    {
        $regle = $this->regleRepository->findByTypeConge($typeCongeId);
        if ($regle) {
            $calcule = (float) $regle->jours_par_mois * 12;
            $plafond = $regle->jours_max !== null ? (float) $regle->jours_max : $calcule;

            return min($calcule, $plafond);
        }

        $type = $this->typeCongeRepository->findById($typeCongeId);
        $max  = (int) ($type->jours_max ?? 0);

        return $max > 0 ? (float) $max : 0.0;
    }
}
