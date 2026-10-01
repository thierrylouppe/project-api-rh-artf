<?php

namespace App\Services;

use App\Enums\StatutAffectation;
use App\Interfaces\AffectationInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\LotAffectationInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\LotAffectation;
use App\Models\User;
use App\Notifications\LotAffectationEvenementNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** @property LotAffectationInterface $repository */
class LotAffectationService extends BaseService
{
    public function __construct(
        LotAffectationInterface $repository,
        private readonly AffectationInterface $affectationRepository,
        private readonly AffectationService $affectationService,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
        private readonly UserInterface $userRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * @param  array{
     *     date_affectation: string,
     *     motif?: string|null,
     *     agents: list<array{
     *         agent_id: int,
     *         structurable_type: string,
     *         structurable_id: int,
     *         superieur_hierarchique_id?: int|null
     *     }>
     * }  $data
     */
    public function creerGroupe(array $data, ?UploadedFile $noteService = null): LotAffectation
    {
        return DB::transaction(function () use ($data, $noteService) {
            $cheminNote = null;
            $nomNote    = null;

            if ($noteService !== null) {
                $cheminNote = $noteService->store('affectations/lots/notes-service', 'local');
                $nomNote    = $noteService->getClientOriginalName();
            }

            $lot = $this->repository->create([
                'date_affectation'           => $data['date_affectation'],
                'motif'                      => $data['motif'] ?? null,
                'note_service'               => $cheminNote,
                'note_service_nom_original'  => $nomNote,
                'statut'                     => StatutAffectation::EN_ATTENTE_VALIDATION,
                'created_by'                 => Auth::id(),
            ]);

            foreach ($data['agents'] as $ligne) {
                $superieurId = ! empty($ligne['superieur_hierarchique_id'])
                    ? (int) $ligne['superieur_hierarchique_id']
                    : $this->affectationRepository->resoudreSuperiorParStructure(
                        $ligne['structurable_type'],
                        (int) $ligne['structurable_id']
                    );

                $this->affectationRepository->create([
                    'agent_id'                   => $ligne['agent_id'],
                    'structurable_type'          => $ligne['structurable_type'],
                    'structurable_id'            => $ligne['structurable_id'],
                    'superieur_hierarchique_id'  => $superieurId,
                    'date_affectation'           => $data['date_affectation'],
                    'motif'                      => $data['motif'] ?? null,
                    'note_service'               => $cheminNote,
                    'note_service_nom_original'  => $nomNote,
                    'statut'                     => StatutAffectation::EN_ATTENTE_VALIDATION,
                    'created_by'                 => Auth::id(),
                    'lot_affectation_id'         => $lot->id,
                ]);
            }

            $this->workflowRepository->initialiserCircuit(LotAffectation::class, $lot->id);

            $this->historiqueRepository->enregistrer(
                LotAffectation::class,
                $lot->id,
                Auth::id(),
                'lot_affectation_cree',
                null,
                ['lignes' => count($data['agents'])],
                null
            );

            $this->notifier($lot, 'creee');

            return $this->repository->findWithLignes($lot->id);
        });
    }

    public function approuver(int $id): LotAffectation
    {
        return DB::transaction(function () use ($id) {
            $lot = $this->repository->findById($id);

            abort_unless(
                $lot->statut->peutTransitionnerVers(StatutAffectation::APPROUVEE),
                422,
                "Le lot ne peut pas être approuvé depuis le statut « {$lot->statut->label()} »."
            );

            $lot->update(['statut' => StatutAffectation::APPROUVEE]);
            $this->affectationRepository->updateStatutByLot($id, StatutAffectation::APPROUVEE);

            $this->historiqueRepository->enregistrer(
                LotAffectation::class,
                $id,
                Auth::id(),
                'lot_affectation_approuve',
                ['statut' => StatutAffectation::EN_ATTENTE_VALIDATION->value],
                ['statut' => StatutAffectation::APPROUVEE->value],
                null
            );

            $this->notifier($lot->fresh(), 'approuvee');

            return $this->repository->findWithLignes($id);
        });
    }

    public function rejeter(int $id, string $commentaire): LotAffectation
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $lot = $this->repository->findById($id);

            abort_unless(
                $lot->statut->peutTransitionnerVers(StatutAffectation::REJETEE),
                422,
                "Le lot ne peut pas être rejeté depuis le statut « {$lot->statut->label()} »."
            );

            $lot->update(['statut' => StatutAffectation::REJETEE]);
            $this->affectationRepository->updateStatutByLot($id, StatutAffectation::REJETEE);

            $this->historiqueRepository->enregistrer(
                LotAffectation::class,
                $id,
                Auth::id(),
                'lot_affectation_rejete',
                null,
                ['statut' => StatutAffectation::REJETEE->value],
                $commentaire
            );

            $this->notifier($lot->fresh(), 'rejetee');

            return $this->repository->findWithLignes($id);
        });
    }

    public function activer(int $id): LotAffectation
    {
        return DB::transaction(function () use ($id) {
            $lot = $this->repository->findById($id);

            abort_unless(
                $lot->statut->peutTransitionnerVers(StatutAffectation::ACTIVE),
                422,
                "Le lot ne peut être activé que depuis le statut « Approuvée ». Statut actuel : « {$lot->statut->label()} »."
            );

            foreach ($this->affectationRepository->getByLot($id) as $ligne) {
                $this->affectationService->activerLigneDeLot((int) $ligne->id);
            }

            $lot->update(['statut' => StatutAffectation::ACTIVE]);

            $this->historiqueRepository->enregistrer(
                LotAffectation::class,
                $id,
                Auth::id(),
                'lot_affectation_active',
                ['statut' => StatutAffectation::APPROUVEE->value],
                ['statut' => StatutAffectation::ACTIVE->value],
                null
            );

            $this->notifier($lot->fresh(), 'activee');

            return $this->repository->findWithLignes($id);
        });
    }

    public function detail(int $id): LotAffectation
    {
        return $this->repository->findWithLignes($id);
    }

    /** @return string Chemin du PDF */
    public function genererActePdf(int $id): string
    {
        $lot = $this->repository->findWithLignes($id);
        $lot->load(['affectations.agent.grade', 'affectations.structure', 'affectations.superieurHierarchique']);

        $pdf = Pdf::loadView('pdf.acte-lot-affectation', [
            'lot'       => $lot,
            'reference' => $this->referenceActe($id),
        ])->setPaper('a4');

        $path = "affectations/lots/{$id}/{$this->nomFichierActe($id)}";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function nomFichierActe(int $id): string
    {
        return $this->referenceActe($id).'.pdf';
    }

    private function referenceActe(int $id): string
    {
        return 'NS-LOT-AFF-'.date('Y').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function notifier(LotAffectation $lot, string $action): void
    {
        if (! $lot->created_by) {
            return;
        }

        $auteur = $this->userRepository->findById((int) $lot->created_by);
        if ($auteur instanceof User) {
            $auteur->notify(new LotAffectationEvenementNotification($lot, $action));
        }
    }
}
