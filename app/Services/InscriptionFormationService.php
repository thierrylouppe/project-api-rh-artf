<?php

namespace App\Services;

use App\Enums\StatutInscriptionFormation;
use App\Enums\TypeActionFormation;
use App\Interfaces\AgentInterface;
use App\Interfaces\CatalogueFormationInterface;
use App\Interfaces\InscriptionFormationInterface;
use App\Interfaces\PlanFormationInterface;
use App\Models\Agent;
use App\Models\CatalogueFormation;
use App\Models\InscriptionFormation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/** @property InscriptionFormationInterface $repository */
class InscriptionFormationService extends BaseService
{
    public function __construct(
        InscriptionFormationInterface $repository,
        private readonly AgentInterface $agentRepository,
        private readonly CatalogueFormationInterface $formationRepository,
        private readonly PlanFormationInterface $planRepository,
    ) {
        parent::__construct($repository);
    }

    public function getByAgent(int $agentId): Collection
    {
        $this->agentRepository->findById($agentId);

        return $this->repository->getByAgent($agentId);
    }

    public function findById(int $id): InscriptionFormation
    {
        return $this->repository->findById($id)
            ->load(['agent:id,matricule,nom,prenom,statut,date_prise_service,date_naissance,echelon_id', 'formation', 'plan']);
    }

    protected function beforeCreate(array $data): array
    {
        $agent = $this->agentRepository->findById((int) $data['agent_id']);
        $formation = $this->formationRepository->findById((int) $data['formation_id']);

        abort_unless($formation instanceof CatalogueFormation && $formation->actif, 422, 'Impossible d\'inscrire à une formation inactive.');
        abort_if(in_array($agent->statut, ['archive', 'stagiaire'], true), 422, 'Cet agent n\'est pas éligible à la formation continue.');

        $this->assertAnciennete($agent, $formation);
        $this->assertDureeInscription($formation, $data);

        if (! empty($data['plan_id'])) {
            $plan = $this->planRepository->findById((int) $data['plan_id']);
            abort_unless(
                $plan->statut?->accepteInscriptions() ?? false,
                422,
                'Les inscriptions ne sont possibles que sur un plan validé ou en exécution.'
            );
            abort_unless(
                $this->planRepository->getLignes((int) $data['plan_id'])->contains('formation_id', (int) $data['formation_id']),
                422,
                'Cette formation n\'est pas prévue dans le plan annuel.'
            );
        }

        abort_if(
            $this->repository->findOuverte((int) $data['agent_id'], (int) $data['formation_id']) !== null,
            422,
            'Une inscription ouverte existe déjà pour cet agent et cette formation.'
        );

        if (! empty($data['admission_sur_titre'])) {
            $this->assertAdmissionSurTitre($agent);
        }

        $data['date_inscription'] = $data['date_inscription'] ?? now()->toDateString();
        $data['created_by'] = Auth::id();
        $data['statut'] = StatutInscriptionFormation::INSCRITE->value;

        if ($formation->debit_formation_mois && ! empty($data['date_fin'])) {
            $data['debit_jusqu_au'] = Carbon::parse($data['date_fin'])
                ->addMonths((int) $formation->debit_formation_mois)
                ->toDateString();
        }

        return $data;
    }

    protected function afterCreate($model): InscriptionFormation
    {
        return $model->load(['agent:id,matricule,nom,prenom,statut', 'formation', 'plan']);
    }

    public function confirmerPresence(int $id): InscriptionFormation
    {
        $inscription = $this->repository->findById($id);
        abort_unless(
            $inscription->statut === StatutInscriptionFormation::INSCRITE,
            422,
            'Seule une inscription au statut inscrite peut être confirmée présente.'
        );

        return $this->repository->update($id, [
            'statut' => StatutInscriptionFormation::PRESENTE->value,
        ])->load(['agent:id,matricule,nom,prenom,statut', 'formation']);
    }

    public function cloturer(int $id, array $data = []): InscriptionFormation
    {
        $inscription = $this->repository->findById($id)->load('formation');
        abort_unless(
            $inscription->statut === StatutInscriptionFormation::PRESENTE
                || $inscription->statut === StatutInscriptionFormation::INSCRITE,
            422,
            'Cette inscription ne peut pas être clôturée.'
        );

        $type = $inscription->formation?->type_action;
        if (in_array($type, [TypeActionFormation::PERFECTIONNEMENT, TypeActionFormation::QUALIFICATION], true)) {
            abort_unless(
                (bool) ($data['rapport_remis'] ?? $inscription->rapport_remis),
                422,
                'Le rapport de fin de stage est obligatoire (CCN art. 100).'
            );
        }

        $payload = [
            'statut' => StatutInscriptionFormation::TERMINEE->value,
            'rapport_remis' => (bool) ($data['rapport_remis'] ?? $inscription->rapport_remis),
        ];
        if (! empty($data['date_fin'])) {
            $payload['date_fin'] = $data['date_fin'];
        }

        return $this->repository->update($id, $payload)
            ->load(['agent:id,matricule,nom,prenom,statut', 'formation']);
    }

    public function annuler(int $id): InscriptionFormation
    {
        $inscription = $this->repository->findById($id);
        abort_unless(
            $inscription->statut?->estOuverte() ?? false,
            422,
            'Seule une inscription ouverte peut être annulée.'
        );

        return $this->repository->update($id, [
            'statut' => StatutInscriptionFormation::ANNULEE->value,
        ])->load(['agent:id,matricule,nom,prenom,statut', 'formation']);
    }

    public function delete(int $id): bool
    {
        $inscription = $this->repository->findById($id);
        abort_unless(
            $inscription->statut === StatutInscriptionFormation::INSCRITE
                || $inscription->statut === StatutInscriptionFormation::ANNULEE,
            422,
            'Impossible de supprimer une inscription déjà suivie ou clôturée.'
        );

        return $this->repository->delete($id);
    }

    private function assertAnciennete(Agent $agent, CatalogueFormation $formation): void
    {
        $min = (int) $formation->anciennete_min_ans;
        if ($min === 0) {
            return;
        }

        abort_if(
            $agent->date_prise_service === null,
            422,
            'La date de prise de service est requise pour vérifier l\'ancienneté (CCN art. 92).'
        );

        $ans = (int) $agent->date_prise_service->diffInYears(now(), true);
        abort_if(
            $ans < $min,
            422,
            "L'agent doit justifier de {$min} ans d'ancienneté pour cette formation (CCN art. 92)."
        );
    }

    private function assertDureeInscription(CatalogueFormation $formation, array $data): void
    {
        $maxMois = $formation->type_action?->dureeMaxMois();
        if ($maxMois === null || empty($data['date_debut']) || empty($data['date_fin'])) {
            return;
        }

        $debut = Carbon::parse($data['date_debut']);
        $fin = Carbon::parse($data['date_fin']);
        abort_if(
            $debut->diffInMonths($fin) > $maxMois,
            422,
            "La durée de cette action ne peut pas excéder {$maxMois} mois (CCN art. 99 / 102)."
        );
    }

    private function assertAdmissionSurTitre(Agent $agent): void
    {
        abort_if(
            $agent->date_naissance === null,
            422,
            'La date de naissance est requise pour une admission sur titre (CCN art. 103).'
        );

        $age = (int) $agent->date_naissance->diffInYears(now(), true);
        abort_if($age > 50, 422, 'L\'admission sur titre est réservée aux agents de 50 ans au plus (CCN art. 103).');

        $agent->loadMissing('echelon');
        abort_unless(
            (int) ($agent->echelon?->numero ?? 0) === 12,
            422,
            'L\'admission sur titre exige d\'avoir atteint le dernier échelon de la classe (CCN art. 103).'
        );
    }
}
