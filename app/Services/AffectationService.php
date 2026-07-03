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
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        });
    }

    public function rejeter(int $id, string $commentaire): Affectation
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $affectation = $this->repository->findById($id);

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

    /**
     * Crée une affectation par agent, chacun vers sa propre structure et son propre supérieur
     * hiérarchique. Seuls date_affectation, motif et note_service sont communs au lot.
     *
     * @param  array{
     *     date_affectation: string,
     *     motif: string|null,
     *     note_service: string|null,
     *     note_service_nom_original: string|null,
     *     agents: array<array{
     *         agent_id: int,
     *         structurable_type: string,
     *         structurable_id: int,
     *         superieur_hierarchique_id: int|null
     *     }>
     * } $data
     * @return Collection<Affectation>
     */
    public function affecterGroupe(array $data): Collection
    {
        return DB::transaction(function () use ($data) {
            $commonData = Arr::except($data, ['agents']);

            return collect($data['agents'])->map(function (array $agentData) use ($commonData) {
                $superieurId = ! empty($agentData['superieur_hierarchique_id'])
                    ? (int) $agentData['superieur_hierarchique_id']
                    : $this->repository->resoudreSuperiorParStructure(
                        $agentData['structurable_type'],
                        (int) $agentData['structurable_id']
                    );

                $payload = array_merge($commonData, $agentData, [
                    'superieur_hierarchique_id' => $superieurId,
                ]);

                return $this->create($payload);
            });
        });
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

        $nomOriginal = "NS-AFF-" . date('Y') . "-" . str_pad($id, 4, '0', STR_PAD_LEFT) . ".pdf";

        $affectation->update([
            'note_service'               => $path,
            'note_service_nom_original'  => $nomOriginal,
        ]);

        return $path;
    }
}
