<?php

namespace App\Services;

use App\Enums\StatutAbsence;
use App\Interfaces\AbsenceInterface;
use App\Interfaces\AgentInterface;
use App\Interfaces\TypeAbsenceInterface;
use App\Models\Absence;
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
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        return $this->repository->getByAgent($agentId);
    }

    public function create(array $data): Absence
    {
        $this->agentRepository->findById((int) $data['agent_id']);
        $type = $this->typeAbsenceRepository->findById((int) $data['type_absence_id']);

        $nbJours = $this->jourFerieService->calculerJoursOuvrables($data['date_debut'], $data['date_fin']);
        abort_if($nbJours < 1, 422, 'La période ne contient aucun jour ouvrable.');

        if ($type->justification_requise && empty($data['motif'])) {
            abort(422, 'Un motif est requis pour ce type d\'absence.');
        }

        $data['nb_jours']  = $nbJours;
        $data['statut']    = StatutAbsence::EN_ATTENTE;
        $data['justifiee'] = $data['justifiee'] ?? false;
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $absence = $this->repository->create($data);
        $absence->load(['agent', 'typeAbsence']);

        $this->notificationService->notifierRole(
            'rh',
            'absence',
            'declaree',
            'Une absence a été déclarée.',
            ['absence_id' => $absence->id, 'agent_id' => $absence->agent_id]
        );

        return $absence;
    }

    public function valider(int $id, ?string $commentaire = null): Absence
    {
        $absence = $this->repository->findById($id);

        abort_unless(
            $absence->statut === StatutAbsence::EN_ATTENTE,
            422,
            'Seule une absence en attente peut être validée.'
        );

        return $this->repository->update($id, [
            'statut'                  => StatutAbsence::VALIDEE,
            'justifiee'               => true,
            'valideur_id'             => Auth::id(),
            'commentaire_validation'  => $commentaire,
        ])->load(['agent', 'typeAbsence']);
    }

    public function rejeter(int $id, string $commentaire): Absence
    {
        $absence = $this->repository->findById($id);

        abort_unless(
            $absence->statut === StatutAbsence::EN_ATTENTE,
            422,
            'Seule une absence en attente peut être rejetée.'
        );

        return $this->repository->update($id, [
            'statut'                 => StatutAbsence::REJETEE,
            'valideur_id'            => Auth::id(),
            'commentaire_validation' => $commentaire,
        ])->load(['agent', 'typeAbsence']);
    }
}
