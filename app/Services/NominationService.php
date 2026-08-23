<?php

namespace App\Services;

use App\Enums\StatutNomination;
use App\Enums\TypeActeNomination;
use App\Interfaces\AffectationInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\NominationInterface;
use App\Interfaces\UserInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\Bureau;
use App\Models\Direction;
use App\Models\Nomination;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NominationEvenementNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** @property NominationInterface $repository */
class NominationService extends BaseService
{
    public function __construct(
        NominationInterface $repository,
        private readonly ValidationWorkflowInterface $workflowRepository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
        private readonly AffectationInterface $affectationRepository,
        private readonly AgentInterface $agentRepository,
        private readonly UserInterface $userRepository,
    ) {
        parent::__construct($repository);
    }

    protected function beforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['statut']     = StatutNomination::EN_ATTENTE;

        return $data;
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $nomination = $this->repository->findById($id);

        abort_unless(
            $nomination->statut === StatutNomination::EN_ATTENTE,
            422,
            'Seule une nomination en attente de validation peut être modifiée.'
        );

        abort_if(
            $nomination->lot_nomination_id,
            422,
            'Cette nomination appartient à un lot : elle ne peut pas être modifiée isolément.'
        );

        unset($data['statut'], $data['created_by']);

        return $data;
    }

    protected function afterCreate($model): Nomination
    {
        $this->workflowRepository->initialiserCircuit(Nomination::class, $model->id);

        $this->historiqueRepository->enregistrer(
            Nomination::class,
            $model->id,
            Auth::id(),
            'nomination_creee',
            null,
            $model->toArray(),
            null
        );

        $this->notifier($model, 'creee');

        return $model;
    }

    public function approuver(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::APPROUVEE),
                422,
                "La nomination ne peut pas être approuvée depuis le statut « {$nomination->statut->label()} »."
            );

            $ancienStatut = $nomination->statut;
            $nomination->update(['statut' => StatutNomination::APPROUVEE]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_approuvee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutNomination::APPROUVEE->value],
                null
            );

            $nomination = $nomination->fresh();
            $this->notifier($nomination, 'approuvee');

            return $nomination;
        });
    }

    public function activer(int $id): Nomination
    {
        return DB::transaction(function () use ($id) {
            $nomination = $this->repository->findById($id);

            abort_if(
                $nomination->lot_nomination_id,
                422,
                'Cette nomination appartient à un lot : activez le lot, pas la ligne.'
            );

            return $this->executerActivation($nomination);
        });
    }

    public function activerLigneDeLot(int $id): Nomination
    {
        $nomination = $this->repository->findById($id);

        abort_unless(
            $nomination->lot_nomination_id,
            422,
            'Cette nomination n\'appartient pas à un lot.'
        );

        return $this->executerActivation($nomination);
    }

    private function executerActivation(\App\Models\Nomination $nomination): \App\Models\Nomination
    {
        $id = (int) $nomination->id;

        abort_unless(
            $nomination->statut->peutTransitionnerVers(StatutNomination::ACTIVE),
            422,
            "La nomination ne peut être activée que depuis le statut « Approuvée ». Statut actuel : « {$nomination->statut->label()} »."
        );

        $this->repository->cloturerActivesPourStructure(
            $nomination->structurable_type,
            $nomination->structurable_id,
            $id
        );
        $this->repository->cloturerActivePourAgent($nomination->agent_id, $id);

        abort_if(
            $this->repository->getActive($nomination->agent_id) !== null,
            422,
            'Cet agent a déjà une nomination active.'
        );

        $nomination->update([
            'statut'     => StatutNomination::ACTIVE,
            'date_debut' => $nomination->date_debut ?? now()->toDateString(),
        ]);

        $this->historiqueRepository->enregistrer(
            Nomination::class,
            $id,
            Auth::id(),
            'nomination_activee',
            ['statut' => StatutNomination::APPROUVEE->value],
            ['statut' => StatutNomination::ACTIVE->value, 'poste' => $nomination->poste],
            null
        );

        $nomination = $nomination->fresh();
        $this->notifier($nomination, 'activee');

        return $nomination;
    }

    public function cloturer(int $id, ?string $dateFin = null): Nomination
    {
        return DB::transaction(function () use ($id, $dateFin) {
            $nomination = $this->repository->findById($id);

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::CLOTUREE),
                422,
                "La nomination ne peut être clôturée que depuis le statut « Active »."
            );

            $cloturee = $this->repository->cloturer($id, $dateFin);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_cloturee',
                ['statut' => StatutNomination::ACTIVE->value],
                ['statut' => StatutNomination::CLOTUREE->value],
                null
            );

            return $cloturee;
        });
    }

    public function rejeter(int $id, string $commentaire): Nomination
    {
        return DB::transaction(function () use ($id, $commentaire) {
            $nomination = $this->repository->findById($id);

            abort_if(
                $nomination->lot_nomination_id,
                422,
                'Cette nomination appartient à un lot : rejetez le lot, pas la ligne.'
            );

            abort_unless(
                $nomination->statut->peutTransitionnerVers(StatutNomination::REJETEE),
                422,
                "La nomination ne peut pas être rejetée depuis le statut « {$nomination->statut->label()} »."
            );

            $ancienStatut = $nomination->statut;
            $nomination->update(['statut' => StatutNomination::REJETEE]);

            $this->historiqueRepository->enregistrer(
                Nomination::class,
                $id,
                Auth::id(),
                'nomination_rejetee',
                ['statut' => $ancienStatut->value],
                ['statut' => StatutNomination::REJETEE->value],
                $commentaire
            );

            $nomination = $nomination->fresh();
            $this->notifier($nomination, 'rejetee');

            return $nomination;
        });
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function getActive(int $agentId): ?Nomination
    {
        return $this->repository->getActive($agentId);
    }

    public function getHistoriqueByAgent(int $agentId): Collection
    {
        return $this->repository->getHistoriqueByAgent($agentId);
    }

    public function postesVacants(): Collection
    {
        return $this->repository->postesVacants();
    }

    /**
     * @return array{chef: \App\Models\Agent, nomination_active: ?Nomination, affectations: Collection}
     */
    public function agentsSousAutorite(int $chefId): array
    {
        $chef = $this->agentRepository->findById($chefId);

        return [
            'chef'              => $chef,
            'nomination_active' => $this->repository->getActive($chefId),
            'affectations'      => $this->affectationRepository->getActivesParSuperieur($chefId),
        ];
    }

    /** @return string Chemin du PDF sur le disque local */
    public function genererActePdf(int $id): string
    {
        $nomination = $this->repository->findById($id);
        $nomination->load(['agent.grade', 'agent.categorie', 'agent.echelon', 'structure']);

        $structure = $nomination->structure;
        if ($structure) {
            match ($nomination->structurable_type) {
                Bureau::class    => $structure->loadMissing('service.direction'),
                Service::class   => $structure->loadMissing('direction'),
                Direction::class => null,
                default          => null,
            };
        }

        $typeActe = $nomination->type_acte ?? TypeActeNomination::DECISION;

        $pdf = Pdf::loadView('pdf.acte-nomination', [
            'nomination' => $nomination,
            'structure'  => $structure,
            'typeActe'   => $typeActe,
            'reference'  => $this->referenceActe($id, $typeActe),
        ])->setPaper('a4');

        $path = "nominations/{$nomination->agent_id}/actes/{$this->nomFichierActe($id)}";
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    public function nomFichierActe(int $id): string
    {
        $nomination = $this->repository->findById($id);
        $typeActe   = $nomination->type_acte ?? TypeActeNomination::DECISION;

        return $this->referenceActe($id, $typeActe).'.pdf';
    }

    private function referenceActe(int $id, TypeActeNomination $typeActe): string
    {
        return $typeActe->prefixeNumero().'-NOM-'.date('Y').'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    private function notifier(Nomination $nomination, string $action): void
    {
        $destinataires = collect();

        if ($nomination->created_by) {
            $auteur = $this->userRepository->findById((int) $nomination->created_by);
            if ($auteur instanceof User) {
                $destinataires->push($auteur);
            }
        }

        $compteAgent = $this->userRepository->findByAgentId((int) $nomination->agent_id);
        if ($compteAgent instanceof User) {
            $destinataires->push($compteAgent);
        }

        $destinataires->unique('id')->each(
            fn (User $user) => $user->notify(new NominationEvenementNotification($nomination, $action))
        );
    }
}
