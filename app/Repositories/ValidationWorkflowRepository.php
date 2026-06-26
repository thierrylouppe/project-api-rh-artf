<?php

namespace App\Repositories;

use App\Enums\NiveauValidation;
use App\Interfaces\ValidationWorkflowInterface;
use App\Models\ValidationWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ValidationWorkflowRepository extends BaseRepository implements ValidationWorkflowInterface
{
    protected function model(): string
    {
        return ValidationWorkflow::class;
    }

    public function initialiserCircuit(string $type, int $id, ?array $niveaux = null): Collection
    {
        // Si aucun circuit configuré, on replie sur le circuit complet par défaut.
        $steps = $niveaux ?? array_map(
            fn (NiveauValidation $n) => ['niveau' => $n->value, 'ordre' => $n->ordre()],
            NiveauValidation::circuitComplet()
        );

        $created = collect();
        foreach ($steps as $step) {
            $created->push(ValidationWorkflow::create([
                'validable_type' => $type,
                'validable_id'   => $id,
                'niveau'         => $step['niveau'],
                'ordre'          => $step['ordre'],
                'statut'         => 'en_attente',
            ]));
        }

        return $created;
    }

    public function getProchainEnAttente(string $type, int $id): ?ValidationWorkflow
    {
        return ValidationWorkflow::where('validable_type', $type)
            ->where('validable_id', $id)
            ->where('statut', 'en_attente')
            ->orderBy('ordre')
            ->first();
    }

    public function approuver(int $id, int $validateurId, ?string $commentaire): ValidationWorkflow
    {
        $validation = $this->findById($id);
        $validation->update([
            'statut'        => 'approuve',
            'validateur_id' => $validateurId,
            'commentaire'   => $commentaire,
            'date_decision' => now(),
        ]);

        return $validation->fresh();
    }

    public function rejeter(int $id, int $validateurId, string $commentaire): ValidationWorkflow
    {
        $validation = $this->findById($id);
        $validation->update([
            'statut'        => 'rejete',
            'validateur_id' => $validateurId,
            'commentaire'   => $commentaire,
            'date_decision' => now(),
        ]);

        return $validation->fresh();
    }

    public function renvoyer(int $id, int $validateurId, string $commentaire): ValidationWorkflow
    {
        $validation = $this->findById($id);
        $validation->update([
            'statut'        => 'renvoye',
            'validateur_id' => $validateurId,
            'commentaire'   => $commentaire,
            'date_decision' => now(),
        ]);

        return $validation->fresh();
    }

    public function circuitTermine(string $type, int $id): bool
    {
        return ! ValidationWorkflow::where('validable_type', $type)
            ->where('validable_id', $id)
            ->where('statut', 'en_attente')
            ->exists();
    }

    public function circuitRejete(string $type, int $id): bool
    {
        return ValidationWorkflow::where('validable_type', $type)
            ->where('validable_id', $id)
            ->where('statut', 'rejete')
            ->exists();
    }

    /** Méthode non applicable sur ce repository (circuit polymorphique) */
    public function getAll(array $filters = []): Collection
    {
        return ValidationWorkflow::query()->get();
    }

    public function create(array $data): Model
    {
        return ValidationWorkflow::create($data);
    }
}
