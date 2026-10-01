<?php

namespace App\Services;

use App\Enums\StatutAbsence;
use App\Interfaces\AbsenceInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\DemandeCongeInterface;
use App\Interfaces\TypeAbsenceInterface;
use App\Interfaces\UserInterface;
use App\Models\Absence;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property AbsenceInterface $repository */
class AbsenceService extends BaseService
{
    public function __construct(
        AbsenceInterface $repository,
        private readonly JourFerieService $jourFerieService,
        private readonly AgentInterface $agentRepository,
        private readonly TypeAbsenceInterface $typeAbsenceRepository,
        private readonly NotificationService $notificationService,
        private readonly DemandeCongeInterface $demandeCongeRepository,
        private readonly SuperieurHierarchiqueService $superieurService,
        private readonly UserInterface $userRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function aValider(): Collection
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');

        return $this->repository->getEnAttente()
            ->filter(fn (Absence $absence) => $this->superieurService->estN1($user, (int) $absence->agent_id))
            ->values();
    }

    public function create(array $data): Absence
    {
        $this->agentRepository->findById((int) $data['agent_id']);
        $type = $this->typeAbsenceRepository->findById((int) $data['type_absence_id']);

        $nbJours = $this->jourFerieService->calculerJoursOuvrables($data['date_debut'], $data['date_fin']);
        abort_if($nbJours < 1, 422, 'La période ne contient aucun jour ouvrable.');

        abort_if(
            $this->repository->chevauchements((int) $data['agent_id'], $data['date_debut'], $data['date_fin'])->isNotEmpty(),
            422,
            'Une absence chevauche déjà cette période.'
        );
        abort_if(
            $this->demandeCongeRepository->chevauchements((int) $data['agent_id'], $data['date_debut'], $data['date_fin'])->isNotEmpty(),
            422,
            'Une demande de congé chevauche déjà cette période.'
        );

        if ($type->justification_requise && empty($data['motif'])) {
            abort(422, 'Un motif est requis pour ce type d\'absence.');
        }

        $data['nb_jours']  = $nbJours;
        $data['statut']    = StatutAbsence::EN_ATTENTE;
        $data['justifiee'] = $data['justifiee'] ?? false;
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $absence = $this->repository->create($data);
        $absence->load(['agent', 'typeAbsence']);

        $this->notifier($absence, 'declaree', 'Une absence a été déclarée.');

        return $absence;
    }

    public function valider(int $id, ?string $commentaire = null): Absence
    {
        $absence = $this->repository->findById($id);
        $user    = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');

        abort_unless(
            $absence->statut === StatutAbsence::EN_ATTENTE,
            422,
            'Seule une absence en attente peut être validée.'
        );

        $this->superieurService->assertEstN1($user, (int) $absence->agent_id);

        $absence = $this->repository->update($id, [
            'statut'                  => StatutAbsence::VALIDEE,
            'justifiee'               => true,
            'valideur_id'             => $user->id,
            'commentaire_validation'  => $commentaire,
        ])->load(['agent', 'typeAbsence']);

        $this->notifier($absence, 'validee', 'L\'absence a été validée par le N+1.');

        return $absence;
    }

    public function rejeter(int $id, string $commentaire): Absence
    {
        $absence = $this->repository->findById($id);
        $user    = Auth::user();
        abort_unless($user instanceof User, 401, 'Non authentifié.');

        abort_unless(
            $absence->statut === StatutAbsence::EN_ATTENTE,
            422,
            'Seule une absence en attente peut être rejetée.'
        );

        $this->superieurService->assertEstN1($user, (int) $absence->agent_id);

        $absence = $this->repository->update($id, [
            'statut'                 => StatutAbsence::REJETEE,
            'valideur_id'            => $user->id,
            'commentaire_validation' => $commentaire,
        ])->load(['agent', 'typeAbsence']);

        $this->notifier($absence, 'rejetee', 'L\'absence a été rejetée par le N+1.');

        return $absence;
    }

    private function notifier(Absence $absence, string $action, string $message): void
    {
        $this->notificationService->notifierRole(
            'rh',
            'absence',
            $action,
            $message,
            ['absence_id' => $absence->id, 'agent_id' => $absence->agent_id]
        );

        $destinataires = collect();
        $compteAgent   = $this->userRepository->findByAgentId((int) $absence->agent_id);
        if ($compteAgent instanceof User) {
            $destinataires->push($compteAgent);
        }
        $n1 = $this->superieurService->trouverCompteN1((int) $absence->agent_id);
        if ($n1 instanceof User) {
            $destinataires->push($n1);
        }

        $this->notificationService->notifierEvenementGroupe(
            $destinataires,
            'absence',
            $action,
            $message,
            ['absence_id' => $absence->id, 'agent_id' => $absence->agent_id]
        );
    }
}
