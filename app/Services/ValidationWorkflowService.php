<?php

namespace App\Services;

use App\Interfaces\HistoriqueIntegrationInterface;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\ValidationWorkflow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** @property ValidationWorkflowInterface $repository */
class ValidationWorkflowService extends BaseService
{
    public function __construct(
        ValidationWorkflowInterface $repository,
        private readonly HistoriqueIntegrationInterface $historiqueRepository,
    ) {
        parent::__construct($repository);
    }

    public function getProchainEnAttente(string $type, int $id): ?ValidationWorkflow
    {
        return $this->repository->getProchainEnAttente($type, $id);
    }

    public function approuver(int $validationId, ?string $commentaire = null): ValidationWorkflow
    {
        return DB::transaction(function () use ($validationId, $commentaire) {
            $cible = $this->repository->findById($validationId);

            abort_unless(
                $cible->estEnAttente(),
                422,
                'Cette validation a déjà été traitée.'
            );

            $prochain = $this->repository->getProchainEnAttente(
                $cible->validable_type,
                $cible->validable_id
            );

            abort_if(
                $prochain && $prochain->id !== $validationId,
                422,
                "Validation hors ordre : le niveau « {$prochain->niveau->label()} » (ordre {$prochain->ordre}) doit être validé en premier."
            );

            $validation = $this->repository->approuver($validationId, Auth::id(), $commentaire);

            $this->historiqueRepository->enregistrer(
                $validation->validable_type,
                $validation->validable_id,
                Auth::id(),
                'validation_approuvee',
                null,
                ['niveau' => $validation->niveau->value, 'statut' => 'approuve'],
                $commentaire
            );

            return $validation;
        });
    }

    public function rejeter(int $validationId, string $commentaire): ValidationWorkflow
    {
        return DB::transaction(function () use ($validationId, $commentaire) {
            $validation = $this->repository->rejeter($validationId, Auth::id(), $commentaire);

            $this->historiqueRepository->enregistrer(
                $validation->validable_type,
                $validation->validable_id,
                Auth::id(),
                'validation_rejetee',
                null,
                ['niveau' => $validation->niveau->value, 'statut' => 'rejete'],
                $commentaire
            );

            return $validation;
        });
    }

    public function renvoyer(int $validationId, string $commentaire): ValidationWorkflow
    {
        return DB::transaction(function () use ($validationId, $commentaire) {
            $validation = $this->repository->renvoyer($validationId, Auth::id(), $commentaire);

            $this->historiqueRepository->enregistrer(
                $validation->validable_type,
                $validation->validable_id,
                Auth::id(),
                'validation_renvoyee',
                null,
                ['niveau' => $validation->niveau->value, 'statut' => 'renvoye'],
                $commentaire
            );

            return $validation;
        });
    }

    public function circuitTermine(string $type, int $id): bool
    {
        return $this->repository->circuitTermine($type, $id);
    }

    public function circuitRejete(string $type, int $id): bool
    {
        return $this->repository->circuitRejete($type, $id);
    }

    public function getCircuit(string $type, int $id): Collection
    {
        return ValidationWorkflow::where('validable_type', $type)
            ->where('validable_id', $id)
            ->orderBy('ordre')
            ->get();
    }
}
