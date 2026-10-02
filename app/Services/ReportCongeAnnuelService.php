<?php

namespace App\Services;

use App\Enums\StatutReportCongeAnnuel;
use App\Interfaces\CongeSoldeInterface;
use App\Interfaces\ReportCongeAnnuelInterface;
use App\Interfaces\TypeCongeInterface;
use App\Models\ReportCongeAnnuel;
use App\Models\TypeConge;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property ReportCongeAnnuelInterface $repository */
class ReportCongeAnnuelService extends BaseService
{
    public function __construct(
        ReportCongeAnnuelInterface $repository,
        private readonly CongeSoldeInterface $soldeRepository,
        private readonly CongeSoldeService $congeSoldeService,
        private readonly TypeCongeInterface $typeCongeRepository,
        private readonly SuperieurHierarchiqueService $superieurService,
    ) {
        parent::__construct($repository);
    }

    public function proposer(array $data): ReportCongeAnnuel
    {
        $user = $this->utilisateur();
        $this->superieurService->assertEstN1($user, (int) $data['agent_id']);

        $anneeSource = (int) $data['annee_source'];
        $anneeCible  = $anneeSource + 1;
        abort_if(
            $this->repository->findActif((int) $data['agent_id'], $anneeSource) !== null,
            422,
            "Un report est déjà proposé ou accordé pour {$anneeSource}."
        );

        $type   = $this->typeAnnuel();
        $source = $this->soldeRepository->findFor((int) $data['agent_id'], (int) $type->id, $anneeSource);
        abort_unless($source, 422, "Aucun solde de congé annuel pour {$anneeSource}.");

        $reliquat = (float) $source->solde_actuel;
        abort_if($reliquat < 1, 422, "Aucun reliquat à reporter pour {$anneeSource}.");
        abort_if(
            $reliquat > 60,
            422,
            "Le report ne peut pas excéder 60 jours ouvrables (deux mois). Reliquat : {$reliquat} j."
        );

        /** @var ReportCongeAnnuel $report */
        $report = $this->repository->create([
            'agent_id'     => (int) $data['agent_id'],
            'annee_source' => $anneeSource,
            'annee_cible'  => $anneeCible,
            'jours'        => $reliquat,
            'motif'        => $data['motif'],
            'statut'       => StatutReportCongeAnnuel::PROPOSE,
            'propose_par'  => $user->id,
        ]);

        return $report->load('agent');
    }

    public function accorder(int $id): ReportCongeAnnuel
    {
        $this->assertRh();

        return DB::transaction(function () use ($id) {
            $report = $this->reportPropose($id);
            $type   = $this->typeAnnuel();

            $this->congeSoldeService->reporterReliquat(
                (int) $report->agent_id,
                (int) $type->id,
                (int) $report->annee_source,
                (int) $report->annee_cible,
                (float) $report->jours
            );

            /** @var ReportCongeAnnuel $accorde */
            $accorde = $this->repository->update($id, [
                'statut'       => StatutReportCongeAnnuel::ACCORDE,
                'decide_par'   => Auth::id(),
                'date_decision'=> now(),
            ]);

            return $accorde->load('agent');
        });
    }

    public function refuser(int $id, string $commentaire): ReportCongeAnnuel
    {
        $this->assertRh();
        $this->reportPropose($id);

        /** @var ReportCongeAnnuel $refuse */
        $refuse = $this->repository->update($id, [
            'statut'               => StatutReportCongeAnnuel::REFUSE,
            'decide_par'           => Auth::id(),
            'commentaire_decision' => $commentaire,
            'date_decision'        => now(),
        ]);

        return $refuse->load('agent');
    }

    private function reportPropose(int $id): ReportCongeAnnuel
    {
        /** @var ReportCongeAnnuel $report */
        $report = $this->repository->findById($id);
        abort_unless(
            $report->statut === StatutReportCongeAnnuel::PROPOSE,
            422,
            'Seule une proposition de report en attente peut être décidée.'
        );

        return $report;
    }

    private function typeAnnuel(): TypeConge
    {
        $type = $this->typeCongeRepository->getAll(['nom' => 'Congé annuel'])->first();
        abort_unless($type instanceof TypeConge, 422, 'Le type Congé annuel est introuvable.');

        return $type;
    }

    private function utilisateur(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');

        return $user;
    }

    private function assertRh(): void
    {
        $user = $this->utilisateur();
        abort_unless(
            $user->hasRole('rh') || $user->hasRole('admin'),
            403,
            'Seuls les RH (DRHL) peuvent décider un report de congé annuel.'
        );
    }
}
