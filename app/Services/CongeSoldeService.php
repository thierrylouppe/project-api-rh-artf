<?php

namespace App\Services;

use App\Interfaces\AgentInterface;
use App\Interfaces\CongeSoldeInterface;
use App\Interfaces\PalierAncienneteCongeInterface;
use App\Interfaces\RegleAcquisitionCongeInterface;
use App\Interfaces\TypeCongeInterface;
use App\Models\CongeSolde;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CongeSoldeService extends BaseService
{
    public function __construct(
        CongeSoldeInterface $repository,
        private readonly RegleAcquisitionCongeInterface $regleRepository,
        private readonly TypeCongeInterface $typeCongeRepository,
        private readonly PalierAncienneteCongeInterface $palierRepository,
        private readonly AgentInterface $agentRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId, ?int $annee = null): Collection
    {
        $cible = $annee ?? (int) now()->year;
        foreach ($this->typeCongeRepository->getDebitantSolde() as $type) {
            $this->getOrCreate($agentId, (int) $type->id, $cible);
        }

        return $this->repository->getByAgent($agentId, $annee);
    }

    public function getOrCreate(int $agentId, int $typeCongeId, int $annee): CongeSolde
    {
        $existant = $this->repository->findFor($agentId, $typeCongeId, $annee);
        if ($existant) {
            return $existant;
        }

        $base   = $this->soldeBase($typeCongeId);
        $bonus  = $this->joursAnciennete($agentId, $annee);
        $initial = $base + $bonus;

        return $this->repository->create([
            'agent_id'         => $agentId,
            'type_conge_id'    => $typeCongeId,
            'annee'            => $annee,
            'solde_initial'    => $initial,
            'solde_actuel'     => $initial,
            'jours_anciennete' => $bonus,
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

    /**
     * Transfère un reliquat de l'année source vers l'année cible.
     * Le cumul reporté ne peut pas dépasser 60 jours ouvrables.
     */
    public function reporterReliquat(int $agentId, int $typeCongeId, int $anneeSource, int $anneeCible, float $joursDemandes): CongeSolde
    {
        $source = $this->repository->findFor($agentId, $typeCongeId, $anneeSource);
        abort_unless($source, 422, "Aucun solde de congé annuel pour {$anneeSource}.");

        $reliquat = (float) $source->solde_actuel;
        abort_if($reliquat < 1, 422, "Aucun reliquat à reporter pour {$anneeSource}.");
        abort_if(
            $reliquat > 60 || $joursDemandes > 60,
            422,
            "Le report ne peut pas excéder 60 jours ouvrables (deux mois). Reliquat : {$reliquat} j."
        );

        $jours = min($joursDemandes, $reliquat);
        $cible = $this->getOrCreate($agentId, $typeCongeId, $anneeCible);
        $deja  = (float) $cible->jours_reportes;
        abort_if(
            $deja + $jours > 60,
            422,
            "Le cumul reporté ne peut pas excéder 60 jours ouvrables. Déjà reportés : {$deja} j., demandés : {$jours} j."
        );

        $this->repository->update($source->id, [
            'solde_actuel' => $reliquat - $jours,
        ]);

        return $this->repository->update($cible->id, [
            'solde_initial'  => (float) $cible->solde_initial + $jours,
            'solde_actuel'   => (float) $cible->solde_actuel + $jours,
            'jours_reportes' => $deja + $jours,
        ]);
    }

    private function soldeBase(int $typeCongeId): float
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

    private function joursAnciennete(int $agentId, int $annee): float
    {
        $agent = $this->agentRepository->findById($agentId);
        if (! $agent->date_prise_service) {
            return 0.0;
        }

        $debutAnnee = Carbon::create($annee, 1, 1)->startOfDay();
        $prise      = $agent->date_prise_service->copy()->startOfDay();
        $annees     = max(0, (int) $prise->diffInYears($debutAnnee));
        $palier     = $this->palierRepository->trouverPour($annees);

        return (float) ($palier?->jours_bonus ?? 0);
    }
}
