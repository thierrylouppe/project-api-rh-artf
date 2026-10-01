<?php

namespace App\Services;

use App\Enums\StatutPlanFormation;
use App\Interfaces\CatalogueFormationInterface;
use App\Interfaces\PlanFormationInterface;
use App\Models\PlanFormation;
use App\Models\PlanFormationLigne;
use Illuminate\Support\Facades\Auth;

/** @property PlanFormationInterface $repository */
class PlanFormationService extends BaseService
{
    public function __construct(
        PlanFormationInterface $repository,
        private readonly CatalogueFormationInterface $formationRepository,
    ) {
        parent::__construct($repository);
    }

    public function findById(int $id): PlanFormation
    {
        return $this->repository->findById($id)
            ->load(['lignes.formation', 'createur:id,name', 'validateur:id,name']);
    }

    protected function beforeCreate(array $data): array
    {
        abort_if(
            $this->repository->findByAnnee((int) $data['annee']) !== null,
            422,
            'Un plan existe déjà pour cette année.'
        );

        $data['created_by'] = Auth::id();
        $data['statut'] = StatutPlanFormation::BROUILLON->value;

        return $data;
    }

    protected function afterCreate($model): PlanFormation
    {
        return $model->load(['lignes.formation', 'createur:id,name']);
    }

    protected function beforeUpdate(int $id, array $data): array
    {
        $plan = $this->repository->findById($id);
        $this->assertBrouillon($plan);

        if (isset($data['annee']) && (int) $data['annee'] !== (int) $plan->annee) {
            abort_if(
                $this->repository->findByAnnee((int) $data['annee']) !== null,
                422,
                'Un plan existe déjà pour cette année.'
            );
        }

        unset($data['statut'], $data['valide_par'], $data['valide_at']);

        return $data;
    }

    public function delete(int $id): bool
    {
        $this->assertBrouillon($this->repository->findById($id));

        return $this->repository->delete($id);
    }

    public function ajouterLigne(int $planId, array $data): PlanFormationLigne
    {
        $plan = $this->repository->findById($planId);
        $this->assertBrouillon($plan);
        $this->formationRepository->findById((int) $data['formation_id']);

        abort_if(
            $this->repository->getLignes($planId)->contains('formation_id', (int) $data['formation_id']),
            422,
            'Cette formation est déjà inscrite au plan.'
        );

        return $this->repository->ajouterLigne($planId, $data);
    }

    public function supprimerLigne(int $planId, int $ligneId): void
    {
        $this->assertBrouillon($this->repository->findById($planId));
        $this->repository->trouverLigne($planId, $ligneId);
        $this->repository->supprimerLigne($ligneId);
    }

    public function valider(int $id): PlanFormation
    {
        $plan = $this->repository->findById($id);
        abort_unless(
            $plan->statut === StatutPlanFormation::BROUILLON,
            422,
            'Seul un plan en brouillon peut être validé.'
        );
        abort_if(
            $this->repository->getLignes($id)->isEmpty(),
            422,
            'Impossible de valider un plan sans formation.'
        );

        return $this->repository->update($id, [
            'statut' => StatutPlanFormation::VALIDE->value,
            'valide_par' => Auth::id(),
            'valide_at' => now(),
        ])->load(['lignes.formation', 'createur:id,name', 'validateur:id,name']);
    }

    public function executer(int $id): PlanFormation
    {
        $plan = $this->repository->findById($id);
        abort_unless(
            $plan->statut === StatutPlanFormation::VALIDE,
            422,
            'Seul un plan validé peut passer en exécution.'
        );

        return $this->repository->update($id, [
            'statut' => StatutPlanFormation::EXECUTE->value,
        ])->load(['lignes.formation', 'createur:id,name', 'validateur:id,name']);
    }

    public function cloturer(int $id): PlanFormation
    {
        $plan = $this->repository->findById($id);
        abort_unless(
            $plan->statut === StatutPlanFormation::EXECUTE,
            422,
            'Seul un plan en exécution peut être clôturé.'
        );

        return $this->repository->update($id, [
            'statut' => StatutPlanFormation::CLOTURE->value,
        ])->load(['lignes.formation', 'createur:id,name', 'validateur:id,name']);
    }

    private function assertBrouillon(PlanFormation $plan): void
    {
        abort_unless(
            $plan->statut?->estModifiable() ?? false,
            422,
            'Seul un plan en brouillon peut être modifié.'
        );
    }
}
