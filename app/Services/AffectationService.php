<?php

namespace App\Services;

use App\Enums\StatutAffectation;
use App\Interfaces\AffectationInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\Affectation;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/** @property AffectationInterface $repository */
class AffectationService extends BaseService
{
    public function __construct(
        AffectationInterface $repository,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['statut']     = StatutAffectation::EN_ATTENTE_VALIDATION;

        // Résolution automatique du supérieur hiérarchique si non fourni
        if (empty($data['superieur_hierarchique_id']) && ! empty($data['structurable_type']) && ! empty($data['structurable_id'])) {
            $data['superieur_hierarchique_id'] = $this->repository->resoudreSuperiorParStructure(
                $data['structurable_type'],
                (int) $data['structurable_id']
            );
        }

        return $data;
    }

    protected function afterCreate($model): Affectation
    {
        $this->workflowRepository->initialiserCircuit(Affectation::class, $model->id);

        $this->historiqueRepository->enregistrer(
            Affectation::class,
            $model->id,
            Auth::id(),
            'affectation_creee',
            null,
            $model->toArray(),
            null
        );

        return $model;
    }

    public function approuver(int $id): Affectation
    {
        return DB::transaction(function () use ($id) {
            $affectation = $this->repository->findById($id);

            abort_unless(
                $affectation->statut->peutTransitionnerVers(StatutAffectation::APPROUVEE),
                422,
                "L'affectation ne peut pas être approuvée depuis le statut « {$affectation->statut->label()} »."
            );

            $ancienStatut = $affectation->statut;
            $affectation->update(['statut' => StatutAffectation::APPROUVEE]);

            $this->historiqueRepository->enregistrer(
                Affectation::class,
                $id,
                Auth::id(),
                'affectation_approuvee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutAffectation::APPROUVEE->value],
                null
            );

            return $affectation->fresh();
        });
    }

    public function activer(int $id): Affectation
    {
        return DB::transaction(function () use ($id) {
            $affectation = $this->repository->findById($id);

            abort_if(
                $affectation->lot_affectation_id,
                422,
                'Cette affectation appartient à un lot : activez le lot, pas la ligne.'
            );

            return $this->executerActivation($affectation);
        });
    }

    public function activerLigneDeLot(int $id): Affectation
    {
        $affectation = $this->repository->findById($id);

        abort_unless(
            $affectation->lot_affectation_id,
            422,
            'Cette affectation n\'appartient pas à un lot.'
        );

        return $this->executerActivation($affectation);
    }

    private function executerActivation(Affectation $affectation): Affectation
    {
        $id = (int) $affectation->id;

        abort_unless(
            $affectation->statut->peutTransitionnerVers(StatutAffectation::ACTIVE),
            422,
            "L'affectation ne peut être activée que depuis le statut « Approuvée ». Statut actuel : « {$affectation->statut->label()} »."
        );

        $ancienneActive = $this->repository->getActive($affectation->agent_id);
        if ($ancienneActive && $ancienneActive->id !== $id) {
            $this->repository->terminer($ancienneActive->id, null);
        }

        $affectation->update(['statut' => StatutAffectation::ACTIVE]);

        $this->historiqueRepository->enregistrer(
            Affectation::class,
            $id,
            Auth::id(),
            'affectation_activee',
            ['statut' => StatutAffectation::APPROUVEE->value],
            ['statut' => StatutAffectation::ACTIVE->value],
            null
        );

        return $affectation->fresh();
    }

    public function rejeter(int $id, string $commentaire): Affectation
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $affectation = $this->repository->findById($id);

            abort_if(
                $affectation->lot_affectation_id,
                422,
                'Cette affectation appartient à un lot : rejetez le lot, pas la ligne.'
            );

            abort_unless(
                $affectation->statut->peutTransitionnerVers(StatutAffectation::REJETEE),
                422,
                "L'affectation ne peut pas être rejetée depuis le statut « {$affectation->statut->label()} »."
            );

            $ancienStatut = $affectation->statut;
            $affectation->update(['statut' => StatutAffectation::REJETEE]);

            $this->historiqueRepository->enregistrer(
                Affectation::class,
                $id,
                Auth::id(),
                'affectation_rejetee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutAffectation::REJETEE->value],
                $commentaire
            );

            return $affectation->fresh();
        });
    }

    public function terminer(int $id, ?string $dateFin = null): Affectation
    {
        $affectation = $this->repository->findById($id);

        abort_unless(
            $affectation->statut->peutTransitionnerVers(StatutAffectation::TERMINEE),
            422,
            "L'affectation ne peut être terminée que depuis le statut « Active »."
        );

        return $this->repository->terminer($id, $dateFin);
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function getActive(int $agentId): ?Affectation
    {
        return $this->repository->getActive($agentId);
    }

    public function creerUnitaire(array $data, ?UploadedFile $noteService = null): Affectation
    {
        if ($noteService !== null) {
            $agentId                           = $data['agent_id'];
            $data['note_service']              = $noteService->store("affectations/{$agentId}/notes-service", 'local');
            $data['note_service_nom_original'] = $noteService->getClientOriginalName();
        }

        return $this->create($data);
    }

    /**
     * Génère la note de service au format PDF (DomPDF), la stocke et met à jour l'affectation.
     *
     * @return string Chemin du fichier généré sur le disque local
     */
    public function genererNoteServicePdf(int $id): string
    {
        $affectation = $this->repository->findById($id);
        $affectation->load(['agent.grade', 'agent.categorie', 'agent.echelon', 'superieurHierarchique', 'structure']);

        $structure = $affectation->structure;
        if ($structure) {
            match ($affectation->structurable_type) {
                Bureau::class    => $structure->loadMissing('service.direction'),
                Service::class   => $structure->loadMissing('direction'),
                Direction::class => null,
                default          => null,
            };
        }

        $pdf = Pdf::loadView('pdf.note-service-affectation', [
            'affectation' => $affectation,
            'structure'   => $structure,
        ])->setPaper('a4');

        $path = "affectations/{$affectation->agent_id}/notes-service/generated/note-service-{$id}.pdf";
        Storage::disk('local')->put($path, $pdf->output());

        $nomOriginal = $this->nomFichierNoteService($id);

        $affectation->update([
            'note_service'               => $path,
            'note_service_nom_original'  => $nomOriginal,
        ]);

        return $path;
    }

    /**
     * @param  list<int>  $ids
     * @return array{path: string, filename: string}
     */
    public function genererNotesServiceZip(array $ids): array
    {
        $tempDir = storage_path('app/temp');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipPath = $tempDir . '/notes-service-lot-' . time() . '.zip';
        $zip     = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($ids as $id) {
            try {
                $path     = $this->genererNoteServicePdf((int) $id);
                $fullPath = Storage::disk('local')->path($path);
                $zip->addFile($fullPath, $this->nomFichierNoteService((int) $id));
            } catch (\Throwable) {
                // Les affectations introuvables ou en erreur sont ignorées.
            }
        }

        $zip->close();

        return [
            'path'     => $zipPath,
            'filename' => 'notes-service-affectations-' . date('Y-m-d') . '.zip',
        ];
    }

    public function nomFichierNoteService(int $id): string
    {
        return 'NS-AFF-' . date('Y') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT) . '.pdf';
    }
}
