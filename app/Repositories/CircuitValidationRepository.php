<?php

namespace App\Repositories;

use App\Interfaces\CircuitValidationInterface;
use App\Models\CircuitValidationTypeIntegration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CircuitValidationRepository extends BaseRepository implements CircuitValidationInterface
{
    protected function model(): string
    {
        return CircuitValidationTypeIntegration::class;
    }

    public function getPourType(int $typeIntegrationId): Collection
    {
        return CircuitValidationTypeIntegration::where('type_integration_id', $typeIntegrationId)
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    public function getCircuitPourType(int $typeIntegrationId): array
    {
        return $this->getPourType($typeIntegrationId)
            ->map(fn ($step) => [
                'niveau' => $step->niveau->value,
                'ordre'  => $step->ordre,
            ])
            ->values()
            ->toArray();
    }

    public function remplacerCircuit(int $typeIntegrationId, array $niveaux): Collection
    {
        return DB::transaction(function () use ($typeIntegrationId, $niveaux) {
            CircuitValidationTypeIntegration::where('type_integration_id', $typeIntegrationId)->delete();

            $created = collect();
            foreach (array_values($niveaux) as $i => $niveau) {
                $created->push(CircuitValidationTypeIntegration::create([
                    'type_integration_id' => $typeIntegrationId,
                    'niveau'              => $niveau,
                    'ordre'               => $i + 1,
                    'actif'               => true,
                ]));
            }

            return $created;
        });
    }

    public function ajouterNiveau(int $typeIntegrationId, string $niveau, int $ordre): CircuitValidationTypeIntegration
    {
        return CircuitValidationTypeIntegration::create([
            'type_integration_id' => $typeIntegrationId,
            'niveau'              => $niveau,
            'ordre'               => $ordre,
            'actif'               => true,
        ]);
    }

    public function supprimerNiveau(int $id): void
    {
        CircuitValidationTypeIntegration::findOrFail($id)->delete();
    }

    public function niveauExiste(int $typeIntegrationId, string $niveau): bool
    {
        return CircuitValidationTypeIntegration::where('type_integration_id', $typeIntegrationId)
            ->where('niveau', $niveau)
            ->exists();
    }
}
