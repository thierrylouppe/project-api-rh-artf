<?php

namespace App\Services;

use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\LotNominationInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\LotNomination;
use App\Models\User;
use App\Notifications\LotNominationEvenementNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** @property LotNominationInterface $repository */
class LotNominationService extends BaseService
{
    public function __construct(
        LotNominationInterface $repository,
        private readonly NominationInterface $nominationRepository,
        private readonly NominationService $nominationService,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
        private readonly UserInterface $userRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * @param  array{
     *     date_debut: string,
     *     type_acte?: string,
     *     agents: list<array{agent_id: int, poste: string, structurable_type: string, structurable_id: int}>
     * }  $data
     */
    public function creerGroupe(array $data): LotNomination
    {
        return DB::transaction(function () use ($data) {
            $lot = $this->repository->create([
                'date_debut' => $data['date_debut'],
                'type_acte'  => $data['type_acte'] ?? TypeActeNomination::DECISION,
                'statut'     => StatutNomination::EN_ATTENTE,
                'created_by' => Auth::id(),
            ]);

            foreach ($data['agents'] as $ligne) {
                $this->nominationRepository->create([
                    'agent_id'           => $ligne['agent_id'],
                    'poste'              => $ligne['poste'],
                    'structurable_type'  => $ligne['structurable_type'],
                    'structurable_id'    => $ligne['structurable_id'],
                    'date_debut'         => $data['date_debut'],
                    'type_acte'          => $data['type_acte'] ?? TypeActeNomination::DECISION,
                    'statut'             => StatutNomination::EN_ATTENTE,
                    'created_by'         => Auth::id(),
                    'lot_nomination_id'  => $lot->id,
                ]);
            }

            $this->workflowRepository->initialiserCircuit(LotNomination::class, $lot->id);

            $this->historiqueRepository->enregistrer(
                LotNomination::class,
                $lot->id,
                Auth::id(),
                'lot_nomination_cree',
                null,
                ['lignes' => count($data['agents'])],
                null
            );

            $this->notifier($lot, 'creee');

            return $this->repository->findWithLignes($lot->id);
        });
    }

    public function approuver(int $id): LotNomination
    {
        return DB::transaction(function () use ($id) {
            $lot = $this->repository->findById($id);

            abort_unless(
                $lot->statut->peutTransitionnerVers(StatutNomination::APPROUVEE),
                422,
                "Le lot ne peut pas être approuvé depuis le statut « {$lot->statut->label()} »."
            );

            $lot->update(['statut' => StatutNomination::APPROUVEE]);
            $this->nominationRepository->updateStatutByLot($id, StatutNomination::APPROUVEE);

            $this->historiqueRepository->enregistrer(
                LotNomination::class,
                $id,
                Auth::id(),
                'lot_nomination_approuve',
                ['statut' => StatutNomination::EN_ATTENTE->value],
                ['statut' => StatutNomination::APPROUVEE->value],
                null
            );

            $this->notifier($lot->fresh(), 'approuvee');

            return $this->repository->findWithLignes($id);
        });
    }

    public function rejeter(int $id, string $commentaire): LotNomination
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $lot = $this->repository->findById($id);

            abort_unless(
                $lot->statut->peutTransitionnerVers(StatutNomination::REJETEE),
                422,
                "Le lot ne peut pas être rejeté depuis le statut « {$lot->statut->label()} »."
            );

            $lot->update(['statut' => StatutNomination::REJETEE]);
            $this->nominationRepository->updateStatutByLot($id, StatutNomination::REJETEE);

            $this->historiqueRepository->enregistrer(
                LotNomination::class,
                $id,
                Auth::id(),
                'lot_nomination_rejete',
                null,
                ['statut' => StatutNomination::REJETEE->value],
                $commentaire
            );

            $this->notifier($lot->fresh(), 'rejetee');

            return $this->repository->findWithLignes($id);
        });
    }

    public function activer(int $id): LotNomination
    {
        return DB::transaction(function () use ($id) {
            $lot = $this->repository->findById($id);

            abort_unless(
                $lot->statut->peutTransitionnerVers(StatutNomination::ACTIVE),
                422,
                "Le lot ne peut être activé que depuis le statut « Approuvée ». Statut actuel : « {$lot->statut->label()} »."
            );

            foreach ($this->nominationRepository->getByLot($id) as $ligne) {
                $this->nominationService->activerLigneDeLot((int) $ligne->id);
            }

            $lot->update(['statut' => StatutNomination::ACTIVE]);

            $this->historiqueRepository->enregistrer(
                LotNomination::class,
                $id,
                Auth::id(),
                'lot_nomination_active',
                ['statut' => StatutNomination::APPROUVEE->value],
                ['statut' => StatutNomination::ACTIVE->value],
                null
            );

            $this->notifier($lot->fresh(), 'activee');

            return $this->repository->findWithLignes($id);
        });
    }

    public function detail(int $id): LotNomination
    {
        return $this->repository->findWithLignes($id);
    }

    /** @return string Chemin du PDF */
    public function genererActePdf(int $id): string
    {
        $lot = $this->repository->findWithLignes($id);
        $lot->load(['nominations.agent.grade', 'nominations.structure']);

        $typeActe = $lot->type_acte ?? TypeActeNomination::DECISION;

        $pdf = Pdf::loadView('pdf.acte-lot-nomination', [
            'lot'       => $lot,
            'typeActe'  => $typeActe,
            'reference' => $this->referenceActe($id, $typeActe),
        ])->setPaper('a4');

        $path = "nominations/lots/{$id}/{$this->nomFichierActe($id)}";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function nomFichierActe(int $id): string
    {
        $lot      = $this->repository->findById($id);
        $typeActe = $lot->type_acte ?? TypeActeNomination::DECISION;

        return $this->referenceActe($id, $typeActe).'.pdf';
    }

    private function referenceActe(int $id, TypeActeNomination $typeActe): string
    {
        return $typeActe->prefixeNumero().'-LOT-NOM-'.date('Y').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function notifier(LotNomination $lot, string $action): void
    {
        if (! $lot->created_by) {
            return;
        }

        $auteur = $this->userRepository->findById((int) $lot->created_by);
        if ($auteur instanceof User) {
            $auteur->notify(new LotNominationEvenementNotification($lot, $action));
        }
    }
}
